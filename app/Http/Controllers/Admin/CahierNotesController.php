<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClasseAnnee;
use App\Models\Eleve;
use App\Models\Note;
use App\Models\Periode;
use App\Models\TypeNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CahierNotesController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // PAGE SÉLECTEUR
    // ─────────────────────────────────────────────────────────────────
    public function index()
    {
        $classesAnnees = ClasseAnnee::with('classe.niveau', 'anneeScolaire')
            ->whereHas('anneeScolaire', fn($q) => $q->where('est_active', 1))
            ->get()
            ->sortBy(fn($ca) => $ca->classe->niveau->ordre);

        $periodes = Periode::with('anneeScolaire')
            ->whereHas('anneeScolaire', fn($q) => $q->where('est_active', 1))
            ->orderBy('id')
            ->get();

        return view('back.pages.cahier-notes.index', compact('classesAnnees', 'periodes'));
    }

    // ─────────────────────────────────────────────────────────────────
    // CAHIER DE NOTES D'UNE CLASSE — UNE PÉRIODE
    // LIGNES = matières  |  COLONNES = élèves
    // GET /admin/cahier-notes/classe/{classeAnnee}?periode_id=2
    // ─────────────────────────────────────────────────────────────────
    public function classe(Request $request, ClasseAnnee $classeAnnee)
    {
        $classeAnnee->load('classe.niveau', 'anneeScolaire');

        $periodeId    = $request->input('periode_id');
        $modeMoyenne  = $request->input('mode_moyenne', 'ponderee');
        $afficherRang = (bool) $request->input('afficher_rang', 1);

        $allPeriodes = Periode::where('annee_scolaire_id', $classeAnnee->annee_scolaire_id)
            ->orderBy('id')->get();

        $periode = $periodeId
            ? $allPeriodes->firstWhere('id', $periodeId)
            : $allPeriodes->first();

        if (!$periode) {
            return redirect()->route('admin.cahier-notes.index')
                ->with('error', 'Aucune période disponible.');
        }

        $typesNotes = TypeNote::orderBy('id')->get();

        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
            ->orderBy('nom')->orderBy('prenom')->get();

        $matieres = DB::table('classe_matieres')
            ->join('matieres', 'matieres.id', '=', 'classe_matieres.matiere_id')
            ->where('classe_matieres.classe_annee_id', $classeAnnee->id)
            ->select('matieres.id', 'matieres.nom_matiere', 'classe_matieres.coefficient')
            ->orderBy('matieres.nom_matiere')->get();

        $matiereIds = $matieres->pluck('id')->all();
        $eleveIds   = $eleves->pluck('id')->all();

        $notesRaw = Note::where('periode_id', $periode->id)
            ->whereIn('eleve_id', $eleveIds)
            ->whereIn('matiere_id', $matiereIds)
            ->get();

        $notes = [];
        foreach ($notesRaw as $n) {
            $notes[$n->eleve_id][$n->matiere_id][$n->type_note_id] = $n->valeur;
        }

        [$moyMatiere, $moyGenerale, $totalCoeff] =
            $this->calculerMoyennes($eleves, $matieres, $typesNotes, $notes, $modeMoyenne);

        $rangs = $this->calculerRangs($eleves, $moyGenerale, $afficherRang);

        $statsMat     = $this->statsParMatiere($eleves, $matieres, $moyMatiere);
        $statsGenerales = $this->statsGenerales($eleves, $moyGenerale);

        return view('back.pages.cahier-notes.classe', compact(
            'classeAnnee', 'allPeriodes', 'periode', 'eleves',
            'matieres', 'typesNotes', 'notes', 'moyMatiere', 'moyGenerale',
            'rangs', 'statsMat', 'statsGenerales', 'totalCoeff',
            'modeMoyenne', 'afficherRang'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // LISTE DES ÉLÈVES D'UNE CLASSE (point d'entrée bulletin)
    // GET /admin/cahier-notes/classe/{classeAnnee}/eleves
    // ─────────────────────────────────────────────────────────────────
    public function listeEleves(Request $request, ClasseAnnee $classeAnnee)
    {
        $classeAnnee->load('classe.niveau', 'anneeScolaire');

        $periodeId   = $request->input('periode_id');
        $modeMoyenne = $request->input('mode_moyenne', 'ponderee');

        $allPeriodes = Periode::where('annee_scolaire_id', $classeAnnee->annee_scolaire_id)
            ->orderBy('id')->get();

        $periode = $periodeId
            ? $allPeriodes->firstWhere('id', $periodeId)
            : $allPeriodes->first();

        $eleves = Eleve::where('classe_annee_id', $classeAnnee->id)
            ->orderBy('nom')->orderBy('prenom')->get();

        // Calcul rapide des moyennes générales pour l'affichage de la liste
        $matieres = DB::table('classe_matieres')
            ->join('matieres', 'matieres.id', '=', 'classe_matieres.matiere_id')
            ->where('classe_matieres.classe_annee_id', $classeAnnee->id)
            ->select('matieres.id', 'matieres.nom_matiere', 'classe_matieres.coefficient')
            ->orderBy('matieres.nom_matiere')->get();

        $typesNotes = TypeNote::orderBy('id')->get();

        $notesRaw = $periode
            ? Note::where('periode_id', $periode->id)
                ->whereIn('eleve_id', $eleves->pluck('id'))
                ->whereIn('matiere_id', $matieres->pluck('id'))
                ->get()
            : collect();

        $notes = [];
        foreach ($notesRaw as $n) {
            $notes[$n->eleve_id][$n->matiere_id][$n->type_note_id] = $n->valeur;
        }

        [$moyMatiere, $moyGenerale, $totalCoeff] =
            $this->calculerMoyennes($eleves, $matieres, $typesNotes, $notes, $modeMoyenne);

        $rangs = $this->calculerRangs($eleves, $moyGenerale, true);

        return view('back.pages.cahier-notes.liste-eleves', compact(
            'classeAnnee', 'allPeriodes', 'periode',
            'eleves', 'moyGenerale', 'rangs', 'modeMoyenne'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // BULLETIN INDIVIDUEL D'UN ÉLÈVE — TOUTES LES PÉRIODES
    // GET /admin/cahier-notes/eleve/{eleve}?mode_moyenne=ponderee
    // ─────────────────────────────────────────────────────────────────
    public function bulletinEleve(Request $request, Eleve $eleve)
    {
        $eleve->load('classeAnnee.classe.niveau', 'classeAnnee.anneeScolaire');
        $classeAnnee = $eleve->classeAnnee;
        $modeMoyenne = $request->input('mode_moyenne', 'ponderee');

        // Toutes les périodes de l'année
        $allPeriodes = Periode::where('annee_scolaire_id', $classeAnnee->annee_scolaire_id)
            ->orderBy('id')->get();

        // Matières + coefficients
        $matieres = DB::table('classe_matieres')
            ->join('matieres', 'matieres.id', '=', 'classe_matieres.matiere_id')
            ->where('classe_matieres.classe_annee_id', $classeAnnee->id)
            ->select('matieres.id', 'matieres.nom_matiere', 'classe_matieres.coefficient')
            ->orderBy('matieres.nom_matiere')->get();

        $typesNotes = TypeNote::orderBy('id')->get();
        $matiereIds = $matieres->pluck('id')->all();
        $totalCoeff = $matieres->sum('coefficient');

        // Toutes les notes de l'élève (toutes périodes)
        $notesRaw = Note::where('eleve_id', $eleve->id)
            ->whereIn('matiere_id', $matiereIds)
            ->get();

        // Index : $notes[periode_id][matiere_id][type_note_id] = valeur
        $notes = [];
        foreach ($notesRaw as $n) {
            $notes[$n->periode_id][$n->matiere_id][$n->type_note_id] = $n->valeur;
        }

        // ── Calculs par période ────────────────────────────────────
        // $moyMatPeriode[periode_id][matiere_id] = moyenne
        // $moyGenPeriode[periode_id]             = moyenne générale
        $moyMatPeriode = [];
        $moyGenPeriode = [];

        foreach ($allPeriodes as $p) {
            foreach ($matieres as $mat) {
                $vals = [];
                foreach ($typesNotes as $tn) {
                    $v = $notes[$p->id][$mat->id][$tn->id] ?? null;
                    if ($v !== null) $vals[] = (float)$v;
                }
                $moyMatPeriode[$p->id][$mat->id] = count($vals)
                    ? round(array_sum($vals) / count($vals), 2)
                    : null;
            }

            // Moyenne générale de la période
            if ($modeMoyenne === 'ponderee') {
                $sumPond = 0; $sumCoeff = 0;
                foreach ($matieres as $mat) {
                    $moy = $moyMatPeriode[$p->id][$mat->id] ?? null;
                    if ($moy === null) continue;
                    $c = (float)($mat->coefficient ?? 1);
                    $sumPond  += $moy * $c;
                    $sumCoeff += $c;
                }
                $moyGenPeriode[$p->id] = $sumCoeff > 0
                    ? round($sumPond / $sumCoeff, 2)
                    : null;
            } else {
                $vals = array_filter(
                    $matieres->map(fn($mat) => $moyMatPeriode[$p->id][$mat->id] ?? null)->all(),
                    fn($v) => $v !== null
                );
                $moyGenPeriode[$p->id] = count($vals)
                    ? round(array_sum($vals) / count($vals), 2)
                    : null;
            }
        }

        // ── Rang de l'élève par période ────────────────────────────
        // On recalcule les moyennes de TOUS les élèves de la classe
        $tousEleves = Eleve::where('classe_annee_id', $classeAnnee->id)
            ->orderBy('nom')->orderBy('prenom')->get();

        $rangsParPeriode = []; // [periode_id] => rang de l'élève courant

        foreach ($allPeriodes as $p) {
            $notesClasse = Note::where('periode_id', $p->id)
                ->whereIn('eleve_id', $tousEleves->pluck('id'))
                ->whereIn('matiere_id', $matiereIds)
                ->get();

            $notesIdx = [];
            foreach ($notesClasse as $n) {
                $notesIdx[$n->eleve_id][$n->matiere_id][$n->type_note_id] = $n->valeur;
            }

            $moysClasse = [];
            foreach ($tousEleves as $e) {
                if ($modeMoyenne === 'ponderee') {
                    $s = 0; $sc = 0;
                    foreach ($matieres as $mat) {
                        $vals = [];
                        foreach ($typesNotes as $tn) {
                            $v = $notesIdx[$e->id][$mat->id][$tn->id] ?? null;
                            if ($v !== null) $vals[] = (float)$v;
                        }
                        $mm = count($vals) ? array_sum($vals) / count($vals) : null;
                        if ($mm !== null) {
                            $s += $mm * (float)($mat->coefficient ?? 1);
                            $sc += (float)($mat->coefficient ?? 1);
                        }
                    }
                    $moysClasse[$e->id] = $sc > 0 ? round($s / $sc, 2) : null;
                } else {
                    $vals = [];
                    foreach ($matieres as $mat) {
                        $mv = [];
                        foreach ($typesNotes as $tn) {
                            $v = $notesIdx[$e->id][$mat->id][$tn->id] ?? null;
                            if ($v !== null) $mv[] = (float)$v;
                        }
                        if ($mv) $vals[] = array_sum($mv) / count($mv);
                    }
                    $moysClasse[$e->id] = count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
                }
            }

            // Trier et trouver le rang
            $sorted = collect($moysClasse)->filter(fn($v) => $v !== null)
                ->sortDesc()->values();
            $rang   = 1;
            foreach ($sorted as $i => $val) {
                if ($i > 0 && $val !== $sorted[$i - 1]) $rang = $i + 1;
                if (isset($moysClasse[$eleve->id]) && abs($val - $moysClasse[$eleve->id]) < 0.001) {
                    $rangsParPeriode[$p->id] = $rang;
                    break;
                }
            }
            if (!isset($rangsParPeriode[$p->id])) {
                $rangsParPeriode[$p->id] = null;
            }
        }

        // ── Statistiques de la classe par matière et par période ───
        // $statsMoyClasse[periode_id][matiere_id] = moy de la classe
        $statsMoyClasse = [];
        foreach ($allPeriodes as $p) {
            $notesClasse = Note::where('periode_id', $p->id)
                ->whereIn('eleve_id', $tousEleves->pluck('id'))
                ->whereIn('matiere_id', $matiereIds)
                ->get();

            $notesIdx = [];
            foreach ($notesClasse as $n) {
                $notesIdx[$n->eleve_id][$n->matiere_id][$n->type_note_id] = $n->valeur;
            }

            foreach ($matieres as $mat) {
                $vals = [];
                foreach ($tousEleves as $e) {
                    $mv = [];
                    foreach ($typesNotes as $tn) {
                        $v = $notesIdx[$e->id][$mat->id][$tn->id] ?? null;
                        if ($v !== null) $mv[] = (float)$v;
                    }
                    if ($mv) $vals[] = array_sum($mv) / count($mv);
                }
                $statsMoyClasse[$p->id][$mat->id] = count($vals)
                    ? round(array_sum($vals) / count($vals), 2)
                    : null;
            }
        }

        // ── Appréciation automatique ───────────────────────────────
        // Basée sur la moyenne générale annuelle (moyenne des périodes)
        $moysPeriodes = array_filter($moyGenPeriode, fn($v) => $v !== null);
        $moyAnnuelle  = count($moysPeriodes)
            ? round(array_sum($moysPeriodes) / count($moysPeriodes), 2)
            : null;

        $appreciation = match(true) {
            $moyAnnuelle === null          => '',
            $moyAnnuelle >= 18             => 'Félicitations — Travail excellent',
            $moyAnnuelle >= 16             => 'Très bien — Continuez vos efforts',
            $moyAnnuelle >= 14             => 'Bien — Bon travail',
            $moyAnnuelle >= 12             => 'Assez bien — Peut mieux faire',
            $moyAnnuelle >= 10             => 'Passable — Efforts insuffisants',
            $moyAnnuelle >= 8              => 'Insuffisant — Travail à améliorer',
            default                        => 'Très insuffisant — Sérieuses lacunes',
        };

        return view('back.pages.cahier-notes.bulletin-eleve', compact(
            'eleve',
            'classeAnnee',
            'allPeriodes',
            'matieres',
            'typesNotes',
            'notes',
            'moyMatPeriode',
            'moyGenPeriode',
            'rangsParPeriode',
            'statsMoyClasse',
            'totalCoeff',
            'modeMoyenne',
            'moyAnnuelle',
            'appreciation',
            'tousEleves'
        ));
    }

    // ─────────────────────────────────────────────────────────────────
    // MÉTHODES PRIVÉES UTILITAIRES
    // ─────────────────────────────────────────────────────────────────

    private function calculerMoyennes($eleves, $matieres, $typesNotes, $notes, $modeMoyenne): array
    {
        $moyMatiere = [];
        $totalCoeff = $matieres->sum('coefficient');

        foreach ($eleves as $eleve) {
            foreach ($matieres as $mat) {
                $vals = [];
                foreach ($typesNotes as $tn) {
                    $v = $notes[$eleve->id][$mat->id][$tn->id] ?? null;
                    if ($v !== null) $vals[] = (float)$v;
                }
                $moyMatiere[$eleve->id][$mat->id] = count($vals)
                    ? round(array_sum($vals) / count($vals), 2)
                    : null;
            }
        }

        $moyGenerale = [];
        foreach ($eleves as $eleve) {
            if ($modeMoyenne === 'ponderee') {
                $s = 0; $sc = 0;
                foreach ($matieres as $mat) {
                    $moy = $moyMatiere[$eleve->id][$mat->id] ?? null;
                    if ($moy === null) continue;
                    $c  = (float)($mat->coefficient ?? 1);
                    $s  += $moy * $c;
                    $sc += $c;
                }
                $moyGenerale[$eleve->id] = $sc > 0 ? round($s / $sc, 2) : null;
            } else {
                $vals = array_filter(
                    $matieres->map(fn($mat) => $moyMatiere[$eleve->id][$mat->id] ?? null)->all(),
                    fn($v) => $v !== null
                );
                $moyGenerale[$eleve->id] = count($vals)
                    ? round(array_sum($vals) / count($vals), 2)
                    : null;
            }
        }

        return [$moyMatiere, $moyGenerale, $totalCoeff];
    }

    private function calculerRangs($eleves, $moyGenerale, $afficherRang): array
    {
        if (!$afficherRang) return [];
        $rangs  = [];
        $sorted = $eleves->sortByDesc(fn($e) => $moyGenerale[$e->id] ?? -1)->values();
        $rang   = 1;
        foreach ($sorted as $i => $e) {
            if ($i > 0) {
                $prev = $sorted[$i - 1];
                if ($moyGenerale[$e->id] !== $moyGenerale[$prev->id]) $rang = $i + 1;
            }
            $rangs[$e->id] = $rang;
        }
        return $rangs;
    }

    private function statsParMatiere($eleves, $matieres, $moyMatiere): array
    {
        $stats = [];
        foreach ($matieres as $mat) {
            $vals = array_filter(
                $eleves->map(fn($e) => $moyMatiere[$e->id][$mat->id] ?? null)->all(),
                fn($v) => $v !== null
            );
            $stats[$mat->id] = [
                'moy' => count($vals) ? round(array_sum($vals) / count($vals), 2) : null,
                'min' => count($vals) ? min($vals) : null,
                'max' => count($vals) ? max($vals) : null,
            ];
        }
        return $stats;
    }

    private function statsGenerales($eleves, $moyGenerale): array
    {
        $vals = array_filter($moyGenerale, fn($v) => $v !== null);
        $n    = count($vals);
        return [
            'moy_classe'    => $n ? round(array_sum($vals) / $n, 2) : null,
            'max'           => $n ? max($vals) : null,
            'min'           => $n ? min($vals) : null,
            'nb_admis'      => count(array_filter($vals, fn($v) => $v >= 10)),
            'taux_reussite' => $n > 0
                ? round(count(array_filter($vals, fn($v) => $v >= 10)) / $n * 100)
                : 0,
        ];
    }
}