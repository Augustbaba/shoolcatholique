@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">
                Gestion des coefficients - {{ $classeAnnee->classe->niveau->nom }} {{ $classeAnnee->classe->suffixe }} ({{ $classeAnnee->anneeScolaire->libelle }})
            </h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ <a href="{{ route('admin.classe-annees.index') }}">Classes par année</a></span>
                <span class="text-secondary-light">/ Coefficients</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Ajouter une matière</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.classe-matieres.store', $classeAnnee) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="matiere_id" class="form-label">Matière</label>
                            <select name="matiere_id" id="matiere_id" class="form-select @error('matiere_id') is-invalid @enderror" required>
                                <option value="">-- Choisir une matière --</option>
                                @foreach($matieres as $matiere)
                                    <option value="{{ $matiere->id }}" {{ old('matiere_id') == $matiere->id ? 'selected' : '' }}>
                                        {{ $matiere->nom_matiere }}
                                    </option>
                                @endforeach
                            </select>
                            @error('matiere_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="coefficient" class="form-label">Coefficient</label>
                            <input type="number" step="0.01" min="0.1" max="10" class="form-control @error('coefficient') is-invalid @enderror" id="coefficient" name="coefficient" value="{{ old('coefficient') }}" required>
                            @error('coefficient')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Matières associées</h5>
                </div>
                <div class="card-body">
                    @if($coefficients->isEmpty())
                        <p class="text-muted">Aucune matière associée pour cette classe.</p>
                    @else
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Coefficient</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($coefficients as $matiere)
                                <tr>
                                    <td>{{ $matiere->nom_matiere }}</td>
                                    <td>
                                        <form action="{{ route('admin.classe-matieres.update', [$classeAnnee, $matiere]) }}" method="POST" class="d-flex align-items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" step="0.01" min="0.1" max="10" name="coefficient" value="{{ $matiere->pivot->coefficient }}" class="form-control form-control-sm" style="width: 80px;">
                                            <button type="submit" class="btn btn-sm btn-primary">Màj</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.classe-matieres.destroy', [$classeAnnee, $matiere]) }}" method="POST" onsubmit="return confirm('Supprimer cette association ?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="ri-delete-bin-line"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection