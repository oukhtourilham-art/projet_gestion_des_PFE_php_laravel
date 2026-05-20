@extends('layouts.app')

@section('title', 'Planning des soutenances')

@section('content')

<h2 class="mb-4">Planning des soutenances</h2>

@if($soutenances->isEmpty())
    <div class="alert alert-info">Aucune soutenance planifiée pour le moment.</div>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle" 
               style="font-size: 13px; min-width: 900px;">
            <thead class="table-dark text-center">
                <tr>
                    <th>#</th>
                    <th>Encadrant</th>
                    <th>Membre Jury 1</th>
                    <th>Membre Jury 2</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Salle</th>
                    <th>Nom Étudiant</th>
                    <th>Prénom Étudiant</th>
                    <th>Filière</th>
                </tr>
            </thead>
            <tbody class="text-center">
                @foreach($soutenances as $i => $s)
                <tr>
                    <td>{{ $i + 1 }}</td>

                    {{-- Encadrant --}}
                    <td>
                        {{ $s->student->encadrant->nom ?? '-' }}
                        {{ $s->student->encadrant->prenom ?? '' }}
                    </td>

                    {{-- Jury 1 --}}
                    <td>
                        @if($s->juries->count() > 0)
                            {{ $s->juries[0]->professor->nom ?? '-' }}
                            {{ $s->juries[0]->professor->prenom ?? '' }}
                        @else
                            -
                        @endif
                    </td>

                    {{-- Jury 2 --}}
                    <td>
                        @if($s->juries->count() > 1)
                            {{ $s->juries[1]->professor->nom ?? '-' }}
                            {{ $s->juries[1]->professor->prenom ?? '' }}
                        @else
                            -
                        @endif
                    </td>

                    <td>{{ \Carbon\Carbon::parse($s->date_soutenance)->format('d/m/Y') ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($s->heure_debut)->format('H:i') ?? '-' }}</td>
                    <td>{{ $s->salle ?? '-' }}</td>
                    <td>{{ $s->student->nom ?? '-' }}</td>
                    <td>{{ $s->student->prenom ?? '-' }}</td>
                    <td>
                        <span class="badge 
                            @if($s->student->filiere == 'GI') bg-primary
                            @elseif($s->student->filiere == 'DATA') bg-success
                            @else bg-warning text-dark
                            @endif">
                            {{ $s->student->filiere ?? '-' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection