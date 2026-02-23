@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">Liste des notes</h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ Notes</span>
            </div>
        </div>
        <a href="{{ route('admin.notes.create') }}" class="btn btn-primary-600 d-flex align-items-center gap-6">
            <i class="ri-add-line"></i> Saisir des notes
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Élève</th>
                        <th>Matière</th>
                        <th>Période</th>
                        <th>Type</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Date saisie</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notes as $note)
                    <tr>
                        <td>{{ $note->id }}</td>
                        <td>{{ $note->eleve->nom }} {{ $note->eleve->prenom }}</td>
                        <td>{{ $note->matiere->nom_matiere }}</td>
                        <td>{{ $note->periode->nom }}</td>
                        <td>{{ $note->typeNote->nom }}</td>
                        <td>{{ $note->valeur }}</td>
                        <td>{{ $note->commentaire }}</td>
                        <td>{{ $note->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $notes->links() }}
        </div>
    </div>
</div>
@endsection