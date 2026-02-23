@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">Prévisualisation de l'import</h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ <a href="{{ route('admin.notes.create') }}">Notes</a></span>
                <span class="text-secondary-light">/ Prévisualisation</span>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Classe :</strong> {{ $classeAnnee->classe->niveau->nom }} {{ $classeAnnee->classe->suffixe }} -
        <strong>Matière :</strong> {{ $matiere->nom_matiere }} -
        <strong>Période :</strong> {{ $periode->nom }} -
        <strong>Type :</strong> {{ $typeNote->nom }}
    </div>

    @if($hasErrors)
        <div class="alert alert-danger">
            Des erreurs ont été détectées dans le fichier. Veuillez les corriger et réimporter.
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($importedData as $item)
                        <tr>
                            <td>{{ $item['matricule'] }}</td>
                            <td>{{ $item['nom'] }}</td>
                            <td>{{ $item['prenom'] }}</td>
                            <td>{{ $item['note'] }}</td>
                            <td>{{ $item['commentaire'] }}</td>
                            <td>
                                @if(!empty($item['errors']))
                                    <span class="badge bg-danger">{{ implode(', ', $item['errors']) }}</span>
                                @else
                                    <span class="badge bg-success">OK</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(!$hasErrors)
                <form action="{{ route('admin.notes.preview') }}" method="GET" class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-check-line"></i> Appliquer ces notes
                    </button>
                    <a href="{{ route('admin.notes.create') }}" class="btn btn-secondary">Annuler</a>
                </form>
            @else
                <a href="{{ route('admin.notes.create') }}" class="btn btn-secondary">Retour</a>
            @endif
        </div>
    </div>
</div>
@endsection