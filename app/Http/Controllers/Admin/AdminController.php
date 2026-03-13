<?php

namespace App\Http\Controllers\Admin;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Communique;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\Parents; // Attention : le modèle s'appelle Parents (avec 's')
use App\Models\Periode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Récupérer l'année scolaire active
        $anneeActive = AnneeScolaire::where('est_active', true)->first();

        // --- Statistiques générales ---
        $totalEleves = Eleve::count();
        $elevesActifs = Eleve::where('statut', 'actif')->count();
        $totalEnseignants = Enseignant::count();
        $totalParents = Parents::count();
        $totalClasses = Classe::count();

        // --- Élèves par sexe ---
        $elevesHommes = Eleve::where('sexe', 'M')->count();
        $elevesFemmes = Eleve::where('sexe', 'F')->count();

        // --- Revenus (paiements) ---
        $totalPaiements = Paiement::sum('montant');
        // Paiements de l'année active (via élève -> classe_annee -> annee_scolaire)
        $paiementsAnnee = Paiement::whereHas('eleve.classeAnnee', function ($q) use ($anneeActive) {
            $q->where('annee_scolaire_id', $anneeActive->id);
        })->sum('montant');

        // --- Dépenses (non gérées dans la base actuelle) ---
        $depenses = 0;

        // --- Derniers communiqués ---
        $communiques = Communique::where('actif', true)
                        ->orderBy('date_publication', 'desc')
                        ->limit(5)
                        ->get();

        // --- Nouvelles inscriptions (30 derniers jours) ---
        $dateLimite = now()->subDays(30);
        $nouvellesInscriptions = Eleve::where('created_at', '>=', $dateLimite)->count();

        // --- Top étudiants (meilleure moyenne sur la dernière période) ---
        $dernierePeriode = Periode::where('annee_scolaire_id', $anneeActive?->id)
                            ->orderBy('date_fin', 'desc')
                            ->first();

        $topEtudiants = collect();
        if ($dernierePeriode) {
            $topEtudiants = Eleve::select('eleves.*', 'notes_avg.moyenne')
                ->joinSub(
                    Note::select('eleve_id', DB::raw('AVG(valeur) as moyenne'))
                        ->where('periode_id', $dernierePeriode->id)
                        ->groupBy('eleve_id'),
                    'notes_avg',
                    function ($join) {
                        $join->on('eleves.id', '=', 'notes_avg.eleve_id');
                    }
                )
                ->orderBy('notes_avg.moyenne', 'desc')
                ->limit(5)
                ->get();
        }

        // --- Répartition des présences (statiques ou à calculer si vous avez la table) ---
        $presentPourcent = 87; // exemple
        $absentPourcent = 40;
        $retardPourcent = 20;
        $demiJourneePourcent = 20;

        // --- Événements à venir ---
        $evenements = Communique::where('type', 'evenement')
                        ->where('date_publication', '>=', now())
                        ->orderBy('date_publication')
                        ->limit(6)
                        ->get();

        // --- Top enseignants (derniers inscrits par exemple) ---
        $topEnseignants = Enseignant::orderBy('created_at', 'desc')->limit(5)->get();

        return view('back.dashboard', compact(
            'totalEleves',
            'elevesActifs',
            'totalEnseignants',
            'totalParents',
            'totalClasses',
            'elevesHommes',
            'elevesFemmes',
            'totalPaiements',
            'paiementsAnnee',
            'depenses',
            'communiques',
            'nouvellesInscriptions',
            'topEtudiants',
            'presentPourcent',
            'absentPourcent',
            'retardPourcent',
            'demiJourneePourcent',
            'evenements',
            'topEnseignants'
        ));
    }
}