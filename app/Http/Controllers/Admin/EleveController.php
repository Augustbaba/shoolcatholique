<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\ClasseAnnee;
use App\Models\Eleve;
use Illuminate\Http\Request;

class EleveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{
    $anneeActive = AnneeScolaire::where('est_active', true)->first();

    if (!$anneeActive) {
        return redirect()->back()->with('error', 'Aucune année scolaire active trouvée.');
    }

    $classes = ClasseAnnee::with('classe.niveau')
                ->where('annee_scolaire_id', $anneeActive->id)
                ->get();

    $selectedClasseId = $request->input('classe_annee_id');
    $eleves = collect();

    if ($selectedClasseId) {
        $eleves = Eleve::with('parentPrincipal')
                ->where('classe_annee_id', $selectedClasseId)
                ->orderBy('nom')
                ->orderBy('prenom')
                ->get();
    }

    return view('back.pages.eleves.index', compact('anneeActive', 'classes', 'selectedClasseId', 'eleves'));
}
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Eleve $eleve)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Eleve $eleve)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Eleve $eleve)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Eleve $eleve)
    {
        //
    }
}
