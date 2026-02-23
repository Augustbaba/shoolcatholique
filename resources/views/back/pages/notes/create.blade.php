@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">Saisie des notes</h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ Notes</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.notes.preview') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="classe_annee_id" class="form-label">Classe - Année</label>
                        <select name="classe_annee_id" id="classe_annee_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            @foreach($classesAnnees as $ca)
                                <option value="{{ $ca->id }}" {{ old('classe_annee_id') == $ca->id ? 'selected' : '' }}>
                                    {{ $ca->classe->niveau->nom }} {{ $ca->classe->suffixe }} - {{ $ca->anneeScolaire->libelle }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="matiere_id" class="form-label">Matière</label>
                        <select name="matiere_id" id="matiere_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            @foreach($matieres as $m)
                                <option value="{{ $m->id }}" {{ old('matiere_id') == $m->id ? 'selected' : '' }}>{{ $m->nom_matiere }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="periode_id" class="form-label">Période</label>
                        <select name="periode_id" id="periode_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            @foreach($periodes as $p)
                                <option value="{{ $p->id }}" {{ old('periode_id') == $p->id ? 'selected' : '' }}>{{ $p->nom }} ({{ $p->anneeScolaire->libelle }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="type_note_id" class="form-label">Type de note</label>
                        <select name="type_note_id" id="type_note_id" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            @foreach($types as $t)
                                <option value="{{ $t->id }}" {{ old('type_note_id') == $t->id ? 'selected' : '' }}>{{ $t->nom }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Préparer la saisie</button>
                <a href="{{ route('admin.notes.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Neutraliser les erreurs liées aux composants inutiles sur cette page
    (function() {
        // Désactiver les fonctions de calendrier et graphiques
        if (typeof displayCalendar === 'function') {
            window.displayCalendar = function() { console.log('Calendrier ignoré'); };
        }
        if (typeof createChartThree === 'function') {
            window.createChartThree = function() { console.log('Graphique ignoré'); };
        }
        // Empêcher les erreurs ApexCharts en vérifiant l'existence des conteneurs
        const originalApex = window.ApexCharts;
        if (originalApex) {
            window.ApexCharts = function(...args) {
                // Vérifier que l'élément existe avant d'instancier
                if (args[0] && document.querySelector(args[0].chart?.selector)) {
                    return new originalApex(...args);
                }
                console.log('ApexCharts ignoré : élément non trouvé');
                return { render: function() {} };
            };
        }
    })();
</script>
@endpush