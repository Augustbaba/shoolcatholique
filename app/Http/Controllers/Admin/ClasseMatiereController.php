<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClasseAnnee;
use App\Models\Matiere;
use App\Models\ClasseMatiere;
use Illuminate\Http\Request;

class ClasseMatiereController extends Controller
{
    /**
     * Affiche la liste des associations pour une classe donnée.
     */
    public function index(ClasseAnnee $classeAnnee)
    {
        $matieres = Matiere::all();
        // Récupérer les matières associées avec leur coefficient (via pivot)
        $coefficients = $classeAnnee->matieres()->get();

        return view('back.pages.classe_matieres.index', compact('classeAnnee', 'matieres', 'coefficients'));
    }

    /**
     * Enregistre une nouvelle association (ou met à jour le coefficient).
     */
    public function store(Request $request, ClasseAnnee $classeAnnee)
    {
        $request->validate([
            'matiere_id'   => 'required|exists:matieres,id',
            'coefficient'  => 'required|numeric|min:0.1|max:10',
        ]);

        // Vérifier si l'association existe déjà
        $exists = ClasseMatiere::where('classe_annee_id', $classeAnnee->id)
                               ->where('matiere_id', $request->matiere_id)
                               ->exists();
        if ($exists) {
            return back()->with('error', 'Cette matière est déjà associée à cette classe.');
        }

        ClasseMatiere::create([
            'classe_annee_id' => $classeAnnee->id,
            'matiere_id'      => $request->matiere_id,
            'coefficient'     => $request->coefficient,
        ]);

        return back()->with('success', 'Matière ajoutée avec succès.');
    }

    /**
     * Met à jour le coefficient.
     */
    public function update(Request $request, ClasseAnnee $classeAnnee, Matiere $matiere)
    {
        $request->validate([
            'coefficient' => 'required|numeric|min:0.1|max:10',
        ]);

        $association = ClasseMatiere::where('classe_annee_id', $classeAnnee->id)
                                     ->where('matiere_id', $matiere->id)
                                     ->firstOrFail();

        $association->update(['coefficient' => $request->coefficient]);

        return back()->with('success', 'Coefficient mis à jour.');
    }

    /**
     * Supprime une association.
     */
    public function destroy(ClasseAnnee $classeAnnee, Matiere $matiere)
    {
        $association = ClasseMatiere::where('classe_annee_id', $classeAnnee->id)
                                     ->where('matiere_id', $matiere->id)
                                     ->firstOrFail();
        $association->delete();

        return back()->with('success', 'Matière retirée de la classe.');
    }
}


