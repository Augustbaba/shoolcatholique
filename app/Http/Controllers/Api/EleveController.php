<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClasseMatiere;
use App\Models\Eleve;
use App\Models\Note;
use Illuminate\Http\Request;

class EleveController extends Controller
{
    public function dernieresNotes($id, Request $request)
    {
        $limit = $request->get('limit', 5);

        $notes = Note::where('eleve_id', $id)
            ->with(['matiere', 'periode', 'typeNote'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'valeur' => (float) $note->valeur, // Forcer en float
                    'matiere_nom' => $note->matiere->nom_matiere,
                    'periode_nom' => $note->periode->nom,
                    'type_note_nom' => $note->typeNote->nom,
                    'commentaire' => $note->commentaire,
                    'created_at' => $note->created_at->diffForHumans(),
                ];
            });

        return response()->json($notes);
    }

    public function toutesNotes($id)
    {
        $eleve = Eleve::with('classeAnnee')->findOrFail($id);

        // Charger tous les coefficients de la classe en une seule requête
        $coefficients = ClasseMatiere::where('classe_annee_id', $eleve->classeAnnee->id)
            ->get()
            ->keyBy('matiere_id'); // Map matiere_id → ClasseMatiere

        $notes = Note::where('eleve_id', $id)
            ->with(['matiere', 'periode', 'typeNote', 'enseignant.user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($note) use ($coefficients) {
                $coefficient = isset($coefficients[$note->matiere_id])
                    ? (float) $coefficients[$note->matiere_id]->coefficient
                    : 1;

                return [
                    'id'             => $note->id,
                    'valeur'         => (float) $note->valeur,
                    'matiere_nom'    => $note->matiere->nom_matiere,
                    'periode_nom'    => $note->periode->nom,
                    'type_note_nom'  => $note->typeNote->nom,
                    'commentaire'    => $note->commentaire,
                    'created_at'     => $note->created_at->format('d/m/Y H:i'),
                    'enseignant_nom' => optional($note->enseignant?->user)->name ?? '-',
                    'coefficient'    => $coefficient,
                    'appreciation'   => $this->getAppreciation($note->valeur),
                ];
            });

        return response()->json($notes);
    }

    public function statistiques($id)
    {
        $notes = Note::where('eleve_id', $id)->get();

        $moyenne = $notes->avg('valeur');
        $meilleure = $notes->max('valeur');
        $plusFaible = $notes->min('valeur');
        $totalNotes = $notes->count();

        return response()->json([
            'moyenne' => round($moyenne, 2),
            'meilleure' => $meilleure,
            'plus_faible' => $plusFaible,
            'total_notes' => $totalNotes,
        ]);
    }

    private function getAppreciation($valeur)
    {
        if ($valeur == 0)    return 'Nul';
        if ($valeur < 6)     return 'Médiocre';
        if ($valeur < 8)     return 'Faible';
        if ($valeur < 10)    return 'Insuffisant';
        if ($valeur < 12)    return 'Passable';
        if ($valeur < 14)    return 'Assez bien';
        if ($valeur < 16)    return 'Bien';
        if ($valeur < 18)    return 'Très bien';
        return 'Excellent';
    }
}
