@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">Liste des parents</h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ Parents</span>
            </div>
        </div>
        <div>
            <a href="{{ route('admin.parents.import.phpoffice') }}" class="btn btn-primary-600 d-flex align-items-center gap-6">
                <i class="ri-upload-line"></i> Importer
            </a>
        </div>
    </div>

    <div class="card h-100">
        <div class="card-body p-0">
            @if(session('success'))
                <div class="alert alert-success m-3">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger m-3">{{ session('error') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Téléphone</th>
                            <th>WhatsApp</th>
                            <th>Profession</th>
                            <th>Adresse</th>
                            <th>Email (utilisateur)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parents as $parent)
                        <tr>
                            <td>{{ $parent->id }}</td>
                            <td>{{ $parent->nom }}</td>
                            <td>{{ $parent->prenom }}</td>
                            <td>{{ $parent->telephone }}</td>
                            <td>{{ $parent->whatsapp ?? '—' }}</td>
                            <td>{{ $parent->profession ?? '—' }}</td>
                            <td>{{ $parent->adresse ?? '—' }}</td>
                            <td>{{ $parent->user->email ?? '—' }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="ri-eye-line me-2"></i>Voir
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="ri-pencil-line me-2"></i>Modifier
                                            </a>
                                        </li>
                                        <li>
                                            <form action="#" method="POST" onsubmit="return confirm('Supprimer ce parent ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ri-delete-bin-line me-2"></i>Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">Aucun parent trouvé.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $parents->links() }}
            </div>
        </div>
    </div>
</div>
@endsection