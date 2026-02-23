@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <div class="breadcrumb d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="fw-semibold mb-4 h6 text-primary-light">Saisie des notes</h1>
            <div>
                <a href="" class="text-secondary-light hover-text-primary hover-underline">Dashboard</a>
                <span class="text-secondary-light">/ <a href="{{ route('admin.notes.create') }}">Notes</a></span>
                <span class="text-secondary-light">/ Saisie</span>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info mb-24">
        <div class="row">
            <div class="col-md-6">
                <strong>Classe :</strong> {{ $classeAnnee->classe->niveau->nom }} {{ $classeAnnee->classe->suffixe }}<br>
                <strong>Année scolaire :</strong> {{ $classeAnnee->anneeScolaire->libelle }}
            </div>
            <div class="col-md-6">
                <strong>Matière :</strong> {{ $matiere->nom_matiere }}<br>
                <strong>Période :</strong> {{ $periode->nom }} &nbsp;|&nbsp;
                <strong>Type :</strong> {{ $typeNote->nom }}
            </div>
        </div>
    </div>

    <!-- Boutons d'export/import -->
    <div class="d-flex gap-2 mb-3">
        <a href="{{ route('admin.notes.export-template') }}" class="btn btn-outline-primary">
            <i class="ri-download-line"></i> Télécharger la fiche vierge
        </a>
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="ri-upload-line"></i> Importer un fichier rempli
        </button>
    </div>

    <!-- Modal d'import -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.notes.import-preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Importer un fichier de notes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="import_file" class="form-label">Fichier Excel</label>
                            <input type="file" class="form-control @error('import_file') is-invalid @enderror" id="import_file" name="import_file" accept=".xlsx,.xls" required>
                            @error('import_file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <p class="text-muted small">Le fichier doit contenir les colonnes : Matricule, Nom, Prénom, Note, Commentaire. La première ligne doit être l'en-tête.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Importer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.notes.store') }}" method="POST">
                @csrf
                <input type="hidden" name="classe_annee_id" value="{{ $classeAnnee->id }}">
                <input type="hidden" name="matiere_id" value="{{ $matiere->id }}">
                <input type="hidden" name="periode_id" value="{{ $periode->id }}">
                <input type="hidden" name="type_note_id" value="{{ $typeNote->id }}">

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th style="width:140px;">Note (sur 20)</th>
                                <th>Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eleves as $index => $eleve)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $eleve->matricule }}</td>
                                <td>{{ $eleve->nom }}</td>
                                <td>{{ $eleve->prenom }}</td>
                                <td>
                                    <input type="number"
                                           step="0.25"
                                           min="0"
                                           max="20"
                                           name="notes[{{ $eleve->id }}][valeur]"
                                           class="form-control form-control-sm"
                                           value="{{ old('notes.'.$eleve->id.'.valeur', $importedNotes[$eleve->id]['valeur'] ?? '') }}"
                                           placeholder="0 - 20">
                                </td>
                                <td>
                                    <input type="text"
                                           name="notes[{{ $eleve->id }}][commentaire]"
                                           class="form-control form-control-sm"
                                           value="{{ old('notes.'.$eleve->id.'.commentaire', $importedNotes[$eleve->id]['commentaire'] ?? '') }}"
                                           placeholder="Optionnel">
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Aucun élève trouvé dans cette classe.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-save-line me-1"></i> Enregistrer les notes
                    </button>
                    <a href="{{ route('admin.notes.create') }}" class="btn btn-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection