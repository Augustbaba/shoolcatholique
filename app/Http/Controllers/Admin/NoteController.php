<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\ClasseAnnee;
use App\Models\Matiere;
use App\Models\Periode;
use App\Models\TypeNote;
use App\Models\Eleve;
use App\Services\ImageNoteExtractorService;
use App\Services\NotesPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Kreait\Firebase\Contract\Messaging;

class NoteController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // NOTIFICATIONS FCM
    // ─────────────────────────────────────────────────────────────────

    private function notifyParents(array $notes, $matiere, $typeNote, Messaging $messaging): void
    {
        $notificationController = new NotificationController($messaging);

        foreach ($notes as $eleveId => $data) {
            if (empty($data['valeur'])) continue;

            $eleve = Eleve::with('parentPrincipal.user')->find($eleveId);
            if (!$eleve || !$eleve->parentPrincipal || !$eleve->parentPrincipal->user) continue;

            $parentUser = $eleve->parentPrincipal->user;
            if (!$parentUser->fcm_token) continue;

            $notificationController->sendFCMNotification(
                $parentUser->fcm_token,
                "Nouvelle note pour {$eleve->prenom}",
                "{$eleve->prenom} a obtenu {$data['valeur']}/20 en {$matiere->nom_matiere} ({$typeNote->nom})",
                ['route' => '/dashboard']
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // INDEX
    // ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $notes = Note::with('eleve', 'matiere', 'periode', 'typeNote')
                     ->latest()
                     ->paginate(20);
        return view('back.pages.notes.index', compact('notes'));
    }

    // ─────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────

    public function create()
    {
        $classesAnnees = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->get();
        $matieres      = Matiere::all();
        $periodes      = Periode::with('anneeScolaire')->get();
        $types         = TypeNote::all();

        return view('back.pages.notes.create', compact('classesAnnees', 'matieres', 'periodes', 'types'));
    }

    // ─────────────────────────────────────────────────────────────────
    // PREVIEW — GET + POST
    // ─────────────────────────────────────────────────────────────────

    public function preview(Request $request)
    {
        // ══════════════════════════════════════════════════════════════
        // GET — rechargement après import (Excel ou IA)
        // ══════════════════════════════════════════════════════════════
        if ($request->isMethod('get')) {
            $criteres = Session::get('note_criteres');
            if (!$criteres) {
                return redirect()->route('admin.notes.create')
                                 ->with('error', 'Session expirée, veuillez recommencer.');
            }

            $classeAnnee = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->find($criteres['classe_annee_id']);
            $matiere     = Matiere::find($criteres['matiere_id']);
            $periode     = Periode::with('anneeScolaire')->find($criteres['periode_id']);
            $typeNote    = TypeNote::find($criteres['type_note_id']);
            $eleves      = Eleve::where('classe_annee_id', $classeAnnee->id)
                                ->orderBy('nom')->orderBy('prenom')->get();

            // ── Priorité 1 : données importées (Excel/IA) en session ──
            $importedData  = Session::get('imported_data', []);
            $importedNotes = [];
            foreach ($importedData as $item) {
                if ($item['eleve_id'] && empty($item['errors'])) {
                    $importedNotes[$item['eleve_id']] = [
                        'valeur'      => $item['note'],
                        'commentaire' => $item['commentaire'],
                    ];
                }
            }

            // ── Priorité 2 : si pas d'import, charger depuis la BDD ──
            if (empty($importedNotes)) {
                $notesExistantes = Note::where('matiere_id',   $matiere->id)
                                       ->where('periode_id',   $periode->id)
                                       ->where('type_note_id', $typeNote->id)
                                       ->whereIn('eleve_id', $eleves->pluck('id'))
                                       ->get()
                                       ->keyBy('eleve_id');

                foreach ($notesExistantes as $eleveId => $note) {
                    $importedNotes[$eleveId] = [
                        'valeur'      => $note->valeur,
                        'commentaire' => $note->commentaire ?? '',
                    ];
                }
            }

            $modeModification = collect($importedNotes)->isNotEmpty();

            return view('back.pages.notes.preview', compact(
                'classeAnnee', 'matiere', 'periode', 'typeNote',
                'eleves', 'importedNotes', 'modeModification'
            ));
        }

        // ══════════════════════════════════════════════════════════════
        // POST — soumission du formulaire critères
        // ══════════════════════════════════════════════════════════════
        $request->validate([
            'classe_annee_id' => 'required|exists:classe_annees,id',
            'matiere_id'      => 'required|exists:matieres,id',
            'periode_id'      => 'required|exists:periodes,id',
            'type_note_id'    => 'required|exists:type_notes,id',
        ]);

        $classeAnnee = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->find($request->classe_annee_id);
        $matiere     = Matiere::find($request->matiere_id);
        $periode     = Periode::with('anneeScolaire')->find($request->periode_id);
        $typeNote    = TypeNote::find($request->type_note_id);

        // Vérifier que la matière est enseignée dans cette classe
        $coefficient = DB::table('classe_matieres')
                         ->where('classe_annee_id', $classeAnnee->id)
                         ->where('matiere_id', $matiere->id)
                         ->first();
        if (!$coefficient) {
            return back()->with('error', 'Cette matière n\'est pas enseignée dans cette classe.');
        }

        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
                        ->orderBy('nom')->orderBy('prenom')
                        ->get();

        // ── VÉRIFICATION BDD : notes existantes pour ces critères ────
        $notesExistantes = Note::where('matiere_id',   $matiere->id)
                               ->where('periode_id',   $periode->id)
                               ->where('type_note_id', $typeNote->id)
                               ->whereIn('eleve_id', $eleves->pluck('id'))
                               ->get()
                               ->keyBy('eleve_id');

        // Pré-remplir le formulaire avec les notes existantes
        $importedNotes = [];
        foreach ($notesExistantes as $eleveId => $note) {
            $importedNotes[$eleveId] = [
                'valeur'      => $note->valeur,
                'commentaire' => $note->commentaire ?? '',
            ];
        }

        // Mode modification si des notes existent déjà
        $modeModification = $notesExistantes->isNotEmpty();

        // Sauvegarder les critères en session (pour export PDF/template)
        Session::put('note_criteres', $request->only([
            'classe_annee_id', 'matiere_id', 'periode_id', 'type_note_id'
        ]));
        // Effacer les données d'import précédentes
        Session::forget(['imported_data', 'ia_meta']);

        return view('back.pages.notes.preview', compact(
            'classeAnnee', 'matiere', 'periode', 'typeNote',
            'eleves', 'importedNotes', 'modeModification'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // EXPORT FICHE VIERGE PDF
    // ─────────────────────────────────────────────────────────────────

    public function exportTemplate(NotesPdfService $pdfService)
    {
        $criteres = Session::get('note_criteres');
        if (!$criteres) {
            return redirect()->route('admin.notes.create')
                             ->with('error', 'Veuillez d\'abord sélectionner les critères.');
        }

        $classeAnnee = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->find($criteres['classe_annee_id']);
        $matiere     = Matiere::find($criteres['matiere_id']);
        $periode     = Periode::with('anneeScolaire')->find($criteres['periode_id']);
        $typeNote    = TypeNote::find($criteres['type_note_id']);

        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
                        ->orderBy('nom')->orderBy('prenom')
                        ->get(['id', 'matricule', 'nom', 'prenom']);

        $notesVides = $eleves->values()->map(fn($e, $i) => [
            'num'         => $i + 1,
            'matricule'   => $e->matricule,
            'nom'         => $e->nom,
            'prenom'      => $e->prenom,
            'note'        => null,
            'commentaire' => '',
        ])->toArray();

        $pdfPath = $pdfService->generateVierge([
            'classe'         => $classeAnnee->classe->niveau->nom . ' ' . $classeAnnee->classe->suffixe,
            'annee_scolaire' => $classeAnnee->anneeScolaire->libelle,
            'matiere'        => $matiere->nom_matiere,
            'periode'        => $periode->nom,
            'type_note'      => $typeNote->nom,
            'enseignant'     => '',
            'notes'          => $notesVides,
        ]);

        $safe     = fn($s) => str_replace([' ', '/', '\\', "'"], '_', $s);
        $filename = sprintf(
            'FicheVierge_%s_%s_%s.pdf',
            $safe($classeAnnee->classe->niveau->nom . $classeAnnee->classe->suffixe),
            $safe($matiere->nom_matiere),
            $safe($periode->nom)
        );

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────
    // IMPORT EXCEL — prévisualisation
    // ─────────────────────────────────────────────────────────────────

    public function importPreview(Request $request)
    {
        $request->validate(['import_file' => 'required|mimes:xlsx,xls|max:2048']);

        $criteres = Session::get('note_criteres');
        if (!$criteres) {
            return redirect()->route('admin.notes.create')
                             ->with('error', 'Session expirée, veuillez recommencer.');
        }

        $classeAnnee  = ClasseAnnee::find($criteres['classe_annee_id']);
        $matiere      = Matiere::find($criteres['matiere_id']);
        $periode      = Periode::find($criteres['periode_id']);
        $typeNote     = TypeNote::find($criteres['type_note_id']);

        $spreadsheet  = IOFactory::load($request->file('import_file')->getRealPath());
        $rows         = $spreadsheet->getActiveSheet()->toArray();
        array_shift($rows); // supprimer l'en-tête

        $eleves       = Eleve::where('classe_annee_id', $classeAnnee->id)
                             ->get(['id', 'matricule', 'nom', 'prenom'])
                             ->keyBy('matricule');
        $importedData = [];

        foreach ($rows as $row) {
            $matricule   = trim($row[0] ?? '');
            $note        = trim($row[3] ?? '');
            $commentaire = trim($row[4] ?? '');

            if (empty($matricule)) continue;

            $lineErrors = [];
            if (!isset($eleves[$matricule])) {
                $lineErrors[] = 'Matricule inconnu';
            }
            if ($note !== '') {
                if (!is_numeric($note) || $note < 0 || $note > 20) {
                    $lineErrors[] = 'Note invalide (0-20)';
                } else {
                    $note = (float) $note;
                }
            }

            $importedData[] = [
                'matricule'   => $matricule,
                'nom'         => $eleves[$matricule]->nom ?? '',
                'prenom'      => $eleves[$matricule]->prenom ?? '',
                'note'        => $note,
                'commentaire' => $commentaire,
                'errors'      => $lineErrors,
                'eleve_id'    => $eleves[$matricule]->id ?? null,
                'matched_by'  => 'excel',
            ];
        }

        $hasErrors = collect($importedData)->filter(fn($i) => !empty($i['errors']))->isNotEmpty();
        Session::put('imported_data', $importedData);

        return view('back.pages.notes.import_preview', compact(
            'importedData', 'hasErrors', 'classeAnnee', 'matiere', 'periode', 'typeNote'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // 📷 IMPORT PAR IMAGE — IA Vision (Mistral)
    // ─────────────────────────────────────────────────────────────────

    public function importImage(Request $request, ImageNoteExtractorService $extractor)
    {
        $request->validate([
            'image_file' => 'required|image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $criteres = Session::get('note_criteres');
        if (!$criteres) {
            return redirect()->route('admin.notes.create')
                             ->with('error', 'Session expirée, veuillez recommencer.');
        }

        // Chemin Windows-safe
        $uploadedFile = $request->file('image_file');
        $filename     = uniqid('note_img_') . '.' . $uploadedFile->getClientOriginalExtension();
        $destDir      = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'notes-images');

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $fullPath = $destDir . DIRECTORY_SEPARATOR . $filename;
        $uploadedFile->move($destDir, $filename);

        if (!file_exists($fullPath)) {
            return back()->with('error', '❌ Erreur : impossible de sauvegarder l\'image temporaire.');
        }

        $classeAnnee = ClasseAnnee::with('classe.niveau')->find($criteres['classe_annee_id']);
        $matiere     = Matiere::find($criteres['matiere_id']);
        $periode     = Periode::find($criteres['periode_id']);
        $typeNote    = TypeNote::find($criteres['type_note_id']);

        $context = [
            'classe'    => $classeAnnee->classe->niveau->nom . ' ' . $classeAnnee->classe->suffixe,
            'matiere'   => $matiere->nom_matiere,
            'periode'   => $periode->nom,
            'type_note' => $typeNote->nom,
        ];

        $result = $extractor->extractNotesFromImage($fullPath, $context);
        @unlink($fullPath);

        if (!$result['success'] || empty($result['notes'])) {
            $err = implode(' | ', $result['errors'] ?: ['Aucune note détectée dans l\'image.']);
            return back()->with('error', '❌ Extraction IA échouée : ' . $err);
        }

        $eleves   = Eleve::where('classe_annee_id', $classeAnnee->id)
                          ->get(['id', 'matricule', 'nom', 'prenom'])
                          ->toArray();
        $matching = $extractor->matchWithDatabase($result['notes'], $eleves);

        $importedData = [];
        foreach ($result['notes'] as $note) {
            $found = collect($eleves)->first(fn($e) =>
                strtoupper($e['matricule']) === strtoupper($note['matricule'] ?? '')
            );
            $importedData[] = [
                'matricule'   => $note['matricule'],
                'nom'         => $note['nom'],
                'prenom'      => $note['prenom'],
                'note'        => $note['note'],
                'commentaire' => $note['commentaire'],
                'eleve_id'    => $found['id'] ?? null,
                'errors'      => $found ? [] : ['Élève non trouvé en base'],
                'matched_by'  => 'ia_vision',
            ];
        }

        Session::put('imported_data', $importedData);
        Session::put('ia_meta', [
            'confidence' => $result['confidence'],
            'warnings'   => $result['warnings'],
            'unmatched'  => count($matching['unmatched']),
        ]);

        $hasErrors = collect($importedData)->filter(fn($i) => !empty($i['errors']))->isNotEmpty();

        return view('back.pages.notes.import_preview', compact(
            'importedData', 'hasErrors', 'classeAnnee', 'matiere', 'periode', 'typeNote'
        ))->with('ia_mode', true);
    }

    // ─────────────────────────────────────────────────────────────────
    // 📄 EXPORT PDF OFFICIEL CCPA
    // ─────────────────────────────────────────────────────────────────

    public function exportPdf(NotesPdfService $pdfService)
    {
        $criteres = Session::get('note_criteres');
        if (!$criteres) {
            return redirect()->route('admin.notes.create')->with('error', 'Session expirée.');
        }

        $classeAnnee = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->find($criteres['classe_annee_id']);
        $matiere     = Matiere::find($criteres['matiere_id']);
        $periode     = Periode::with('anneeScolaire')->find($criteres['periode_id']);
        $typeNote    = TypeNote::find($criteres['type_note_id']);
        $enseignant  = Auth::user()->enseignant->nom_complet ?? Auth::user()->name ?? 'N/A';

        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
                        ->orderBy('nom')->orderBy('prenom')
                        ->get();

        $notesData = [];
        foreach ($eleves as $i => $eleve) {
            $note = Note::where('eleve_id',     $eleve->id)
                        ->where('matiere_id',   $matiere->id)
                        ->where('periode_id',   $periode->id)
                        ->where('type_note_id', $typeNote->id)
                        ->first();

            $notesData[] = [
                'num'         => $i + 1,
                'matricule'   => $eleve->matricule,
                'nom'         => $eleve->nom,
                'prenom'      => $eleve->prenom,
                'note'        => $note?->valeur ?? null,
                'commentaire' => $note?->commentaire ?? '',
            ];
        }

        $pdfPath = $pdfService->generate([
            'classe'         => $classeAnnee->classe->niveau->nom . ' ' . $classeAnnee->classe->suffixe,
            'annee_scolaire' => $classeAnnee->anneeScolaire->libelle,
            'matiere'        => $matiere->nom_matiere,
            'periode'        => $periode->nom,
            'type_note'      => $typeNote->nom,
            'enseignant'     => $enseignant,
            'notes'          => $notesData,
        ]);

        $safe     = fn($s) => str_replace([' ', '/', '\\'], '_', $s);
        $filename = sprintf(
            'FicheNotes_%s_%s_%s.pdf',
            $safe($classeAnnee->classe->niveau->nom . $classeAnnee->classe->suffixe),
            $safe($matiere->nom_matiere),
            $safe($periode->nom)
        );

        return response()->download($pdfPath, $filename, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }

    // ─────────────────────────────────────────────────────────────────
    // STORE — enregistrement (création ou mise à jour)
    // ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'classe_annee_id'     => 'required|exists:classe_annees,id',
            'matiere_id'          => 'required|exists:matieres,id',
            'periode_id'          => 'required|exists:periodes,id',
            'type_note_id'        => 'required|exists:type_notes,id',
            'notes'               => 'required|array',
            'notes.*.valeur'      => 'nullable|numeric|min:0|max:20',
            'notes.*.commentaire' => 'nullable|string|max:255',
        ]);

        $matiere      = Matiere::find($request->matiere_id);
        $periode      = Periode::find($request->periode_id);
        $typeNote     = TypeNote::find($request->type_note_id);
        $enseignantId = Auth::user()->enseignant->id ?? 1;

        DB::beginTransaction();
        $created = 0;
        $updated = 0;

        try {
            foreach ($request->notes as $eleveId => $data) {
                if (!isset($data['valeur']) || $data['valeur'] === '') continue;

                $existing = Note::where('eleve_id',     $eleveId)
                                ->where('matiere_id',   $matiere->id)
                                ->where('periode_id',   $periode->id)
                                ->where('type_note_id', $typeNote->id)
                                ->first();

                $payload = [
                    'valeur'        => $data['valeur'],
                    'commentaire'   => $data['commentaire'] ?? null,
                    'enseignant_id' => $enseignantId,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    Note::create(array_merge($payload, [
                        'eleve_id'     => $eleveId,
                        'matiere_id'   => $matiere->id,
                        'periode_id'   => $periode->id,
                        'type_note_id' => $typeNote->id,
                    ]));
                    $created++;
                }
            }

            DB::commit();

            $this->notifyParents($request->notes, $matiere, $typeNote, app(Messaging::class));

            Session::forget(['note_criteres', 'imported_data', 'ia_meta']);

            $msg = [];
            if ($created) $msg[] = "$created note(s) créée(s)";
            if ($updated) $msg[] = "$updated note(s) mise(s) à jour";

            return redirect()->route('admin.notes.index')
                             ->with('success', implode(', ', $msg) . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage())->withInput();
        }
    }
}