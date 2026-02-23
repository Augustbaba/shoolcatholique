<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use Illuminate\Http\Request;

class AnneeScolaireController extends Controller
{
    /**
     * Affiche la liste des années scolaires.
     */
    public function index()
    {
        $annees = AnneeScolaire::orderBy('date_debut', 'desc')->paginate(10);
        return view('back.pages.annees_scolaires.index', compact('annees'));
    }

    /**
     * Affiche le formulaire de création.
     */
    public function create()
    {
        return view('back.pages.annees_scolaires.create');
    }

    /**
     * Enregistre une nouvelle année scolaire.
     */
    public function store(Request $request)
    {
        $request->validate([
            'libelle'     => 'required|string|max:9|unique:annee_scolaires',
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after:date_debut',
            'est_active'  => 'sometimes|boolean',
        ]);

        $data = $request->all();
        $data['est_active'] = $request->has('est_active'); // car checkbox non coché = absent

        AnneeScolaire::create($data);

        return redirect()->route('admin.annees-scolaires.index')
                         ->with('success', 'Année scolaire créée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition.
     */
    public function edit(AnneeScolaire $anneeScolaire)
{
    return view('back.pages.annees_scolaires.edit', compact('anneeScolaire'));
}

    /**
     * Met à jour l'année scolaire.
     */
    public function update(Request $request, AnneeScolaire $anneeScolaire)
    {
        $request->validate([
            'libelle'     => 'required|string|max:9|unique:annee_scolaires,libelle,' . $anneeScolaire->id,
            'date_debut'  => 'required|date',
            'date_fin'    => 'required|date|after:date_debut',
            'est_active'  => 'sometimes|boolean',
        ]);

        $data = $request->all();
        $data['est_active'] = $request->has('est_active');

        $anneeScolaire->update($data);

        return redirect()->route('admin.annees-scolaires.index')
                         ->with('success', 'Année scolaire mise à jour.');
    }

    /**
     * Supprime une année scolaire (si non active).
     */
    public function destroy(AnneeScolaire $anneeScolaire)
    {
        try {
            $anneeScolaire->delete();
            return redirect()->route('admin.annees-scolaires.index')
                             ->with('success', 'Année scolaire supprimée.');
        } catch (\Exception $e) {
            return redirect()->route('admin.annees-scolaires.index')
                             ->with('error', 'Suppression impossible : ' . $e->getMessage());
        }
    }
}