<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Note;
use App\Models\Classe;
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
    // INDEX avec filtres et tris
    // ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Note::with(['eleve.classeAnnee.classe.niveau', 'matiere', 'periode', 'typeNote']);
        
        // Application des filtres
        if ($request->filled('annee_scolaire_id')) {
            $query->whereHas('eleve.classeAnnee', function($q) use ($request) {
                $q->where('annee_scolaire_id', $request->annee_scolaire_id);
            });
        }
        
        if ($request->filled('classe_id')) {
            $query->whereHas('eleve.classeAnnee', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }
        
        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }
        
        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }
        
        if ($request->filled('type_note_id')) {
            $query->where('type_note_id', $request->type_note_id);
        }
        
        // Filtre par plage de dates
        if ($request->filled('date_range')) {
            switch($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)
                          ->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }
        
        // Recherche par élève
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('eleve', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }
        
        // Filtre par note minimale
        if ($request->filled('note_min')) {
            $query->where('valeur', '>=', (float)$request->note_min);
        }
        
        // Filtre par note maximale
        if ($request->filled('note_max')) {
            $query->where('valeur', '<=', (float)$request->note_max);
        }
        
        // Application du tri
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        switch($sort) {
            case 'id':
                $query->orderBy('id', $direction);
                break;
            case 'classe':
                $query->join('eleves', 'notes.eleve_id', '=', 'eleves.id')
                      ->join('classe_annees', 'eleves.classe_annee_id', '=', 'classe_annees.id')
                      ->join('classes', 'classe_annees.classe_id', '=', 'classes.id')
                      ->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
                      ->orderBy('niveaux.ordre', $direction)
                      ->orderBy('classes.suffixe', $direction)
                      ->select('notes.*');
                break;
            case 'eleve':
                $query->join('eleves', 'notes.eleve_id', '=', 'eleves.id')
                      ->orderBy('eleves.nom', $direction)
                      ->orderBy('eleves.prenom', $direction)
                      ->select('notes.*');
                break;
            case 'matiere':
                $query->join('matieres', 'notes.matiere_id', '=', 'matieres.id')
                      ->orderBy('matieres.nom_matiere', $direction)
                      ->select('notes.*');
                break;
            case 'periode':
                $query->join('periodes', 'notes.periode_id', '=', 'periodes.id')
                      ->orderBy('periodes.created_at', $direction)
                      ->select('notes.*');
                break;
            case 'valeur':
                $query->orderBy('valeur', $direction);
                break;
            default:
                $query->orderBy('created_at', $direction);
        }
        
        $notes = $query->paginate(20);
        
        // Données pour les filtres
        $anneesScolaires = AnneeScolaire::orderBy('libelle', 'desc')->get();
        $classes = Classe::with('niveau')->orderByFullName()->get();
        $periodes = Periode::with('anneeScolaire')->orderBy('created_at', 'desc')->get();
        $matieres = Matiere::orderBy('nom_matiere')->get();
        $typeNotes = TypeNote::orderBy('nom')->get();
        
        // Variables pour la vue
        $sortBy = $sort;
        $sortDirection = $direction;
        
        return view('back.pages.notes.index', compact(
            'notes', 'anneesScolaires', 'classes', 'periodes', 
            'matieres', 'typeNotes', 'sortBy', 'sortDirection'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // EXPORT EXCEL
    // ─────────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $query = Note::with(['eleve.classeAnnee.classe.niveau', 'matiere', 'periode', 'typeNote']);
        
        // Appliquer les mêmes filtres que pour l'index
        if ($request->filled('annee_scolaire_id')) {
            $query->whereHas('eleve.classeAnnee', function($q) use ($request) {
                $q->where('annee_scolaire_id', $request->annee_scolaire_id);
            });
        }
        
        if ($request->filled('classe_id')) {
            $query->whereHas('eleve.classeAnnee', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id);
            });
        }
        
        if ($request->filled('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }
        
        if ($request->filled('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }
        
        if ($request->filled('type_note_id')) {
            $query->where('type_note_id', $request->type_note_id);
        }
        
        if ($request->filled('date_range')) {
            switch($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', today()->subDay());
                    break;
                case 'this_week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $query->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'last_month':
                    $query->whereMonth('created_at', now()->subMonth()->month)
                          ->whereYear('created_at', now()->subMonth()->year);
                    break;
            }
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('eleve', function($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('note_min')) {
            $query->where('valeur', '>=', (float)$request->note_min);
        }
        
        if ($request->filled('note_max')) {
            $query->where('valeur', '<=', (float)$request->note_max);
        }
        
        // Application du tri pour l'export
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        switch($sort) {
            case 'classe':
                $query->join('eleves', 'notes.eleve_id', '=', 'eleves.id')
                      ->join('classe_annees', 'eleves.classe_annee_id', '=', 'classe_annees.id')
                      ->join('classes', 'classe_annees.classe_id', '=', 'classes.id')
                      ->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
                      ->orderBy('niveaux.ordre', $direction)
                      ->orderBy('classes.suffixe', $direction)
                      ->select('notes.*');
                break;
            case 'eleve':
                $query->join('eleves', 'notes.eleve_id', '=', 'eleves.id')
                      ->orderBy('eleves.nom', $direction)
                      ->orderBy('eleves.prenom', $direction)
                      ->select('notes.*');
                break;
            case 'matiere':
                $query->join('matieres', 'notes.matiere_id', '=', 'matieres.id')
                      ->orderBy('matieres.nom_matiere', $direction)
                      ->select('notes.*');
                break;
            case 'valeur':
                $query->orderBy('valeur', $direction);
                break;
            default:
                $query->orderBy('created_at', $direction);
        }
        
        $notes = $query->get();
        
        // Création du fichier Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Titre
        $sheet->setTitle('Export Notes');
        
        // En-têtes
        $headers = ['ID', 'Classe', 'Élève', 'Matricule', 'Matière', 'Période', 'Type de note', 'Note /20', 'Commentaire', 'Date de saisie'];
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        
        // Style des en-têtes
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        
        foreach ($headers as $i => $header) {
            $sheet->setCellValue($columns[$i] . '1', $header);
            $sheet->getStyle($columns[$i] . '1')->applyFromArray($headerStyle);
        }
        
        // Largeurs des colonnes
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(40);
        $sheet->getColumnDimension('J')->setWidth(18);
        
        // Remplissage des données
        $row = 2;
        foreach ($notes as $note) {
            $classeName = $note->eleve->classeAnnee->classe->full_name ?? 
                          ($note->eleve->classeAnnee->classe->niveau->nom ?? '') . ' ' . 
                          ($note->eleve->classeAnnee->classe->suffixe ?? '');
            
            $sheet->setCellValue('A' . $row, $note->id);
            $sheet->setCellValue('B' . $row, trim($classeName));
            $sheet->setCellValue('C' . $row, $note->eleve->nom . ' ' . $note->eleve->prenom);
            $sheet->setCellValue('D' . $row, $note->eleve->matricule ?? 'N/A');
            $sheet->setCellValue('E' . $row, $note->matiere->nom_matiere);
            $sheet->setCellValue('F' . $row, $note->periode->nom);
            $sheet->setCellValue('G' . $row, $note->typeNote->nom);
            $sheet->setCellValue('H' . $row, $note->valeur);
            $sheet->setCellValue('I' . $row, $note->commentaire);
            $sheet->setCellValue('J' . $row, $note->created_at->format('d/m/Y H:i:s'));
            
            // Alternance des couleurs
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':J' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
            }
            
            $row++;
        }
        
        // Freeze la première ligne
        $sheet->freezePane('A2');
        
        // Téléchargement
        $filename = 'notes_export_' . date('Y-m-d_His') . '.xlsx';
        
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
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
    // EXPORT FICHE VIERGE EXCEL (template import)
    // ─────────────────────────────────────────────────────────────────

    public function exportTemplateExcel()
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

        // ── Styles réutilisables ─────────────────────────────────────
        $borderAll = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'BDBDBD'],
                ],
            ],
        ];
        $styleTitre = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13, 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $styleColHeader = array_merge([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10, 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ], $borderAll);
        $styleExemple = array_merge([
            'font'      => ['italic' => true, 'color' => ['rgb' => '9E9E9E'], 'size' => 9, 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ], $borderAll);
        $styleLocked = array_merge([
            'font'      => ['name' => 'Arial', 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ], $borderAll);
        $styleSaisie = array_merge([
            'font'      => ['name' => 'Arial', 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ], $borderAll);
        $styleWarning = [
            'font' => ['italic' => true, 'color' => ['rgb' => 'E65100'], 'size' => 8, 'name' => 'Arial'],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        // ── Feuille 1 : Import Notes ─────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Import Notes');

        // Titre
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'FICHE DE SAISIE DES NOTES — IMPORTATION');
        $sheet->getStyle('A1')->applyFromArray($styleTitre);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Métadonnées (lignes 2-5)
        $labelStyle = [
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $valueStyle = [
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => '1565C0']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $meta = [
            2 => ['Classe - Année scolaire :', $classeAnnee->classe->niveau->nom . ' ' . $classeAnnee->classe->suffixe . ' — ' . $classeAnnee->anneeScolaire->libelle],
            3 => ['Matière :', $matiere->nom_matiere],
            4 => ['Période :', $periode->nom],
            5 => ['Type de note :', $typeNote->nom],
        ];
        foreach ($meta as $row => [$label, $value]) {
            $sheet->setCellValue("B{$row}", $label);
            $sheet->getStyle("B{$row}")->applyFromArray($labelStyle);
            $sheet->mergeCells("C{$row}:E{$row}");
            $sheet->setCellValue("C{$row}", $value);
            $sheet->getStyle("C{$row}")->applyFromArray($valueStyle);
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // Séparateur ligne 6
        $sheet->getRowDimension(6)->setRowHeight(6);

        // En-têtes colonnes (ligne 7)
        $cols    = ['A', 'B', 'C', 'D', 'E'];
        $headers = ['Matricule', 'Nom', 'Prénom', 'Note (/20)', 'Commentaire'];
        foreach ($headers as $i => $h) {
            $coord = $cols[$i] . '7';
            $sheet->setCellValue($coord, $h);
            $sheet->getStyle($coord)->applyFromArray($styleColHeader);
        }
        $sheet->getRowDimension(7)->setRowHeight(22);

        // Ligne exemple (ligne 8)
        $example = ['EX-001', 'EXEMPLE', 'Prénom', 15.5, 'Bon travail'];
        foreach ($example as $i => $val) {
            $coord = $cols[$i] . '8';
            $sheet->setCellValue($coord, $val);
            $sheet->getStyle($coord)->applyFromArray($styleExemple);
        }
        $sheet->getRowDimension(8)->setRowHeight(18);

        // Données élèves (à partir de la ligne 9)
        foreach ($eleves as $i => $eleve) {
            $row = $i + 9;
            $sheet->setCellValue("A{$row}", $eleve->matricule);
            $sheet->setCellValue("B{$row}", $eleve->nom);
            $sheet->setCellValue("C{$row}", $eleve->prenom);
            // Colonnes D et E vierges — à saisir par l'utilisateur

            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($styleLocked);
            $sheet->getStyle("D{$row}:E{$row}")->applyFromArray($styleSaisie);
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        // Note de bas de page
        $lastRow = $eleves->count() + 9;
        $sheet->mergeCells("A{$lastRow}:E{$lastRow}");
        $sheet->setCellValue(
            "A{$lastRow}",
            '⚠  Colonnes A, B, C (Matricule / Nom / Prénom) : ne pas modifier. '
            . 'Colonne D : valeur numérique entre 0 et 20. Laissez vide si absent.'
        );
        $sheet->getStyle("A{$lastRow}")->applyFromArray($styleWarning);
        $sheet->getRowDimension($lastRow)->setRowHeight(16);

        // Largeurs colonnes
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(13);
        $sheet->getColumnDimension('E')->setWidth(38);

        // Figer après ligne d'en-têtes + exemple
        $sheet->freezePane('A9');

        // ── Feuille 2 : Instructions ─────────────────────────────────
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Instructions');

        $sheet2->mergeCells('A1:B1');
        $sheet2->setCellValue('A1', "INSTRUCTIONS D'IMPORTATION");
        $sheet2->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 13, 'name' => 'Arial'],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet2->getRowDimension(1)->setRowHeight(28);

        $lines = [
            3 => "1. Ne supprimez pas et ne modifiez pas la ligne exemple (ligne 8) — elle sera ignorée à l'import.",
            4 => "2. Remplissez uniquement les colonnes D (Note /20) et E (Commentaire).",
            5 => "3. Les colonnes A, B, C (Matricule, Nom, Prénom) sont pré-remplies — ne pas les modifier.",
            6 => "4. La note doit être un nombre entre 0 et 20, avec au max 2 décimales (ex : 14, 7.5, 0).",
            7 => "5. Laissez la cellule Note vide si l'élève est absent ou non évalué.",
            8 => "6. Sauvegardez le fichier au format .xlsx avant de l'importer dans le système.",
            10 => "Pour toute question, contactez l'administrateur du système scolaire.",
        ];
        foreach ($lines as $r => $txt) {
            $sheet2->setCellValue("A{$r}", $txt);
            $sheet2->getStyle("A{$r}")->getFont()->setName('Arial')->setSize(10);
            if ($r === 10) {
                $sheet2->getStyle("A{$r}")->getFont()->setItalic(true)->getColor()->setRGB('757575');
            }
            $sheet2->getRowDimension($r)->setRowHeight(18);
        }
        $sheet2->getColumnDimension('A')->setWidth(90);

        $spreadsheet->setActiveSheetIndex(0);

        // ── Envoi en téléchargement ───────────────────────────────────
        $safe     = fn($s) => str_replace([' ', '/', '\\', "'"], '_', $s);
        $filename = sprintf(
            'Template_Notes_%s_%s_%s.xlsx',
            $safe($classeAnnee->classe->niveau->nom . $classeAnnee->classe->suffixe),
            $safe($matiere->nom_matiere),
            $safe($periode->nom)
        );

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
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
        array_shift($rows); // supprimer l'en-tête (ligne 7)
        array_shift($rows); // supprimer la ligne exemple (ligne 8)

        $eleves       = Eleve::where('classe_annee_id', $classeAnnee->id)
                             ->get(['id', 'matricule', 'nom', 'prenom'])
                             ->keyBy('matricule');
        $importedData = [];

        foreach ($rows as $row) {
            $matricule   = trim($row[0] ?? '');
            $note        = trim(str_replace([',', ' '], ['.', ''], (string) ($row[3] ?? '')));
            $commentaire = trim($row[4] ?? '');

            if (empty($matricule)) continue;

            $lineErrors = [];
            if (!isset($eleves[$matricule])) {
                $lineErrors[] = 'Matricule inconnu';
            }
            if ($note !== '') {
                if (!is_numeric($note) || (float) $note < 0 || (float) $note > 20) {
                    $lineErrors[] = 'Note invalide (0-20)';
                } else {
                    $note = round((float) $note, 2);
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
    // IMPORT PAR IMAGE — IA Vision (Mistral)
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
    // EXPORT PDF OFFICIEL CCPA
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
            'notes.*.valeur'      => 'nullable|numeric|min:0|max:20|regex:/^\d+(\.\d{1,2})?$/',
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