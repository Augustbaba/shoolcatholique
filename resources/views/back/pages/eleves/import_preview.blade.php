@extends('back.layouts.master')

@section('content')
<div class="dashboard-main-body">
    <!-- ... breadcrumb ... -->

    <div class="alert alert-info">
        <strong>Classe-année sélectionnée :</strong> {{ $classeAnnee->classe->niveau->nom }} {{ $classeAnnee->classe->suffixe }} - {{ $classeAnnee->anneeScolaire->libelle }}
    </div>

    @if(!empty($errors))
        <div class="alert alert-warning">
            <h5>Erreurs détectées (lignes ignorées)</h5>
            <ul>
                @foreach($errors as $line => $errs)
                    <li>Ligne {{ $line }} : {{ implode(', ', $errs) }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if(empty($data))
                <p class="text-center">Aucune donnée valide à importer.</p>
                <a href="{{ route('admin.eleves.import.create') }}" class="btn btn-secondary">Retour</a>
            @else
                <form action="{{ route('admin.eleves.import.store') }}" method="POST">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all" checked></th>
                                    <th>Matricule</th>
                                    <th>Nom</th>
                                    <th>Prénom</th>
                                    <th>Sexe</th>
                                    <th>Date naissance</th>
                                    <th>Tél parent</th>
                                    <th>Parent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $item)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected[]" value="{{ $item['index'] }}" checked>
                                    </td>
                                    <td>{{ $item['matricule'] }}</td>
                                    <td>{{ $item['nom'] }}</td>
                                    <td>{{ $item['prenom'] }}</td>
                                    <td>{{ $item['sexe'] ?? '—' }}</td>
                                    <td>{{ $item['date_naissance'] ? \Carbon\Carbon::parse($item['date_naissance'])->format('d/m/Y') : '—' }}</td>
                                    <td>{{ $item['telephone_parent'] }}</td>
                                    <td>{{ $item['nom_parent'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Importer les lignes sélectionnées</button>
                        <a href="{{ route('admin.eleves.import.create') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('select-all').addEventListener('change', function(e) {
        let checkboxes = document.querySelectorAll('input[name="selected[]"]');
        checkboxes.forEach(cb => cb.checked = e.target.checked);
    });
</script>
@endpush