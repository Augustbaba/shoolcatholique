@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">Importer des élèves</h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ Élèves</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('admin.eleves.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="classe_annee_id" class="form-label">Classe - Année <span class="text-danger">*</span></label>
                    <select name="classe_annee_id" id="classe_annee_id" class="form-select @error('classe_annee_id') is-invalid @enderror" required>
                        <option value="">-- Sélectionnez la classe et l'année --</option>
                        @foreach($classesAnnees as $ca)
                            <option value="{{ $ca->id }}" {{ old('classe_annee_id') == $ca->id ? 'selected' : '' }}>
                                {{ $ca->classe->niveau->nom }} {{ $ca->classe->suffixe }} - {{ $ca->anneeScolaire->libelle }}
                            </option>
                        @endforeach
                    </select>
                    @error('classe_annee_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="file" class="form-label">Fichier Excel (xlsx, xls, csv)</label>
                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" required accept=".xlsx,.xls,.csv">
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Prévisualiser</button>
                <a href="{{ route('admin.eleves.index') }}" class="btn btn-secondary">Annuler</a>
            </form>

            <hr class="my-4">

            <h6>Format attendu :</h6>
            <p>Le fichier Excel doit contenir les colonnes suivantes (dans cet ordre) : <strong>NOM, PRÉNOM, SEXE (M/F), DATE DE NAISSANCE (JJ/MM/AAAA), TÉLÉPHONE PARENT, NOM PARENT</strong>.</p>
            <p>Le téléphone parent doit être unique. Si le parent n'existe pas, il sera créé automatiquement avec le nom fourni.</p>
            <p>Les élèves seront rattachés à la classe-année sélectionnée ci-dessus.</p>
        </div>
    </div>
</div>
@endsection