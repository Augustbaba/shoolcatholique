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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Kreait\Firebase\Contract\Messaging;

class NoteController extends Controller
{
    private function notifyParents(array $notes, $matiere, $typeNote, Messaging $messaging): void
    {
        $notificationController = new NotificationController($messaging);

        foreach ($notes as $eleveId => $data) {
            if (empty($data['valeur'])) {
                continue;
            }

            // Récupérer l'élève avec son parent
            $eleve = Eleve::with('parentPrincipal.user')->find($eleveId);

            if (!$eleve || !$eleve->parentPrincipal || !$eleve->parentPrincipal->user) {
                continue;
            }

            $parentUser = $eleve->parentPrincipal->user;

            if (!$parentUser->fcm_token) {
                continue; // Ce parent n'a pas de token FCM
            }

            $titre = "Nouvelle note pour {$eleve->prenom}";
            $corps = "{$eleve->prenom} a obtenu {$data['valeur']}/20 en {$matiere->nom_matiere} ({$typeNote->nom})";

            $notificationController->sendFCMNotification(
                $parentUser->fcm_token,
                $titre,
                $corps,
                ['route' => '/dashboard']
                // ['route' => '/notes', 'eleve_id' => (string) $eleveId]
            );
        }
    }


    public function index()
    {
        $notes = Note::with('eleve', 'matiere', 'periode', 'typeNote')
                    ->latest()
                    ->paginate(20);
        return view('back.pages.notes.index', compact('notes'));
    }

    public function create()
    {
        $classesAnnees = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->get();
        $matieres = Matiere::all();
        $periodes = Periode::with('anneeScolaire')->get();
        $types = TypeNote::all();

        return view('back.pages.notes.create', compact('classesAnnees', 'matieres', 'periodes', 'types'));
    }

    public function preview(Request $request)
    {
        // Requête GET (après import ou rechargement)
        if ($request->isMethod('get')) {
            $criteres = Session::get('note_criteres');
            if (!$criteres) {
                return redirect()->route('admin.notes.create')->with('error', 'Session expirée, veuillez recommencer.');
            }

            $classeAnnee = ClasseAnnee::with('classe.niveau', 'anneeScolaire')->find($criteres['classe_annee_id']);
            $matiere     = Matiere::find($criteres['matiere_id']);
            $periode     = Periode::with('anneeScolaire')->find($criteres['periode_id']);
            $typeNote    = TypeNote::find($criteres['type_note_id']);
            $eleves      = Eleve::where('classe_annee_id', $classeAnnee->id)
                                ->orderBy('nom')->orderBy('prenom')->get();

            // Récupérer les données importées depuis la session (si existantes)
            $importedData = Session::get('imported_data', []);
            $importedNotes = [];
            foreach ($importedData as $item) {
                if ($item['eleve_id'] && empty($item['errors'])) {
                    $importedNotes[$item['eleve_id']] = [
                        'valeur'      => $item['note'],
                        'commentaire' => $item['commentaire'],
                    ];
                }
            }

            return view('back.pages.notes.preview', compact(
                'classeAnnee', 'matiere', 'periode', 'typeNote', 'eleves', 'importedNotes'
            ));
        }

        // Requête POST : validation des critères et affichage initial
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

        // Récupérer la liste des élèves de cette classe
        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
                        ->orderBy('nom')
                        ->orderBy('prenom')
                        ->get();

        // Stocker les critères en session pour les étapes suivantes
        Session::put('note_criteres', $request->all());
        // S'il y avait des données importées précédemment, on les garde (elles seront fusionnées plus tard)

        return view('back.pages.notes.preview', compact(
            'classeAnnee',
            'matiere',
            'periode',
            'typeNote',
            'eleves'
        ));
    }

    public function exportTemplate()
    {
        $criteres = Session::get('note_criteres');
        if (!$criteres) {
            return redirect()->route('admin.notes.create')->with('error', 'Veuillez d\'abord sélectionner les critères.');
        }

        $classeAnnee = ClasseAnnee::with('classe.niveau')->find($criteres['classe_annee_id']);
        $matiere     = Matiere::find($criteres['matiere_id']);
        $periode     = Periode::find($criteres['periode_id']);
        $typeNote    = TypeNote::find($criteres['type_note_id']);

        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
                        ->orderBy('nom')->orderBy('prenom')
                        ->get(['id', 'matricule', 'nom', 'prenom']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Matricule');
        $sheet->setCellValue('B1', 'Nom');
        $sheet->setCellValue('C1', 'Prénom');
        $sheet->setCellValue('D1', 'Note (sur 20)');
        $sheet->setCellValue('E1', 'Commentaire');

        $row = 2;
        foreach ($eleves as $eleve) {
            $sheet->setCellValue('A' . $row, $eleve->matricule);
            $sheet->setCellValue('B' . $row, $eleve->nom);
            $sheet->setCellValue('C' . $row, $eleve->prenom);
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = sprintf(
            'notes_%s_%s_%s_%s.xlsx',
            $classeAnnee->classe->niveau->nom . $classeAnnee->classe->suffixe,
            $matiere->nom_matiere,
            $periode->nom,
            $typeNote->nom
        );
        $filename = str_replace([' ', '/', '\\'], '_', $filename);

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    public function importPreview(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,xls|max:2048',
        ]);

        $criteres = Session::get('note_criteres');
        if (!$criteres) {
            return redirect()->route('admin.notes.create')->with('error', 'Session expirée, veuillez recommencer la sélection.');
        }

        $classeAnnee = ClasseAnnee::find($criteres['classe_annee_id']);
        $matiere     = Matiere::find($criteres['matiere_id']);
        $periode     = Periode::find($criteres['periode_id']);
        $typeNote    = TypeNote::find($criteres['type_note_id']);

        $file = $request->file('import_file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Supprimer la ligne d'en-tête
        array_shift($rows);

        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
                        ->get(['id', 'matricule', 'nom', 'prenom'])
                        ->keyBy('matricule');

        $importedData = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $matricule = trim($row[0] ?? '');
            $note = trim($row[3] ?? '');
            $commentaire = trim($row[4] ?? '');

            if (empty($matricule)) {
                continue;
            }

            $lineErrors = [];

            if (!isset($eleves[$matricule])) {
                $lineErrors[] = "Matricule inconnu";
            }

            if (!empty($note)) {
                if (!is_numeric($note) || $note < 0 || $note > 20) {
                    $lineErrors[] = "Note invalide (doit être entre 0 et 20)";
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
            ];
        }

        $hasErrors = collect($importedData)->filter(fn($item) => !empty($item['errors']))->isNotEmpty();

        Session::put('imported_data', $importedData);

        return view('back.pages.notes.import_preview', compact('importedData', 'hasErrors', 'classeAnnee', 'matiere', 'periode', 'typeNote'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'classe_annee_id' => 'required|exists:classe_annees,id',
            'matiere_id'      => 'required|exists:matieres,id',
            'periode_id'      => 'required|exists:periodes,id',
            'type_note_id'    => 'required|exists:type_notes,id',
            'notes'           => 'required|array',
            'notes.*.valeur'  => 'nullable|numeric|min:0|max:20',
            'notes.*.commentaire' => 'nullable|string|max:255',
        ]);

        $classeAnnee = ClasseAnnee::find($request->classe_annee_id);
        $matiere     = Matiere::find($request->matiere_id);
        $periode     = Periode::find($request->periode_id);
        $typeNote    = TypeNote::find($request->type_note_id);

        // Récupération de l'enseignant connecté (si pas d'enseignant, on met 1 par défaut)
        $enseignantId = Auth::user()->enseignant->id ?? 1;

        DB::beginTransaction();
        $imported = 0;
        $updated = 0;

        try {
            foreach ($request->notes as $eleveId => $data) {
                if (!empty($data['valeur'])) {
                    $note = Note::where('eleve_id', $eleveId)
                                ->where('matiere_id', $matiere->id)
                                ->where('periode_id', $periode->id)
                                ->where('type_note_id', $typeNote->id)
                                ->first();

                    if ($note) {
                        $note->update([
                            'valeur'        => $data['valeur'],
                            'commentaire'   => $data['commentaire'] ?? null,
                            'enseignant_id' => $enseignantId,
                        ]);
                        $updated++;
                    } else {
                        Note::create([
                            'eleve_id'      => $eleveId,
                            'matiere_id'    => $matiere->id,
                            'periode_id'    => $periode->id,
                            'type_note_id'  => $typeNote->id,
                            'valeur'        => $data['valeur'],
                            'commentaire'   => $data['commentaire'] ?? null,
                            'enseignant_id' => $enseignantId,
                        ]);
                        $imported++;
                    }
                }
            }
            DB::commit();

            $this->notifyParents($request->notes, $matiere, $typeNote, app(Messaging::class));

            // Nettoyer les sessions
            Session::forget(['note_criteres', 'imported_data']);

            $message = "$imported notes créées, $updated notes mises à jour.";
            return redirect()->route('admin.notes.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage())
                         ->withInput();
        }
    }
}
