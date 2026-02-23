@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">Liste des élèves</h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ Élèves</span>
            </div>
        </div>
        <div>
            <a href="{{ route('admin.eleves.import.create') }}" class="btn btn-primary-600 d-flex align-items-center gap-6">
                <i class="ri-upload-line"></i> Importer
            </a>
        </div>
    </div>

    <div class="card mb-24">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.eleves.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="classe_annee_id" class="form-label">Classe (année active : {{ $anneeActive->libelle }})</label>
                    <select name="classe_annee_id" id="classe_annee_id" class="form-select">
                        <option value="">-- Choisir une classe --</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" {{ $selectedClasseId == $classe->id ? 'selected' : '' }}>
                                {{ $classe->classe->niveau->nom }} {{ $classe->classe->suffixe }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedClasseId)
        <div class="card">
            <div class="card-body p-0">
                @if($eleves->isEmpty())
                    <p class="text-muted p-3">Aucun élève dans cette classe.</p>
                @else
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Sexe</th>
                                <th>Date naissance</th>
                                <th>Parent</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eleves as $eleve)
                            <tr>
                                <td>{{ $eleve->matricule }}</td>
                                <td>{{ $eleve->nom }}</td>
                                <td>{{ $eleve->prenom }}</td>
                                <td>{{ $eleve->sexe }}</td>
                                <td>{{ $eleve->date_naissance->format('d/m/Y') }}</td>
                                <td>{{ $eleve->parentPrincipal->nom }} {{ $eleve->parentPrincipal->prenom }}</td>
                                <td>
                                    @if($eleve->statut == 'actif')
                                        <span class="badge bg-success">Actif</span>
                                    @elseif($eleve->statut == 'inactif')
                                        <span class="badge bg-secondary">Inactif</span>
                                    @else
                                        <span class="badge bg-dark">Ancien</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#"><i class="ri-eye-line"></i> Voir</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="ri-pencil-line"></i> Modifier</a></li>
                                            <li>
                                                <form action="#" method="POST" onsubmit="return confirm('Supprimer cet élève ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="ri-delete-bin-line"></i> Supprimer</button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-info">
            Veuillez sélectionner une classe pour afficher les élèves.
        </div>
    @endif
</div>
@endsection