@extends('layouts.app')

@section('title', 'Planning des soutenances')

@section('content')

    <h2>Planning des soutenances</h2>

    @if($soutenances->isEmpty())
        <p>Aucune soutenance planifiée pour le moment.</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Encadrant</th>
                    <th>Membre jury 1</th>
                    <th>Membre jury 2</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Salle</th>
                    <th>Nom étudiant</th>
                    <th>Prénom étudiant</th>
                    <th>Filière</th>
                </tr>
            </thead>
            <tbody>
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

                    <td>{{ $s->date_soutenance ?? '-' }}</td>

                    <td>{{ $s->heure_debut ?? '-' }}</td>

                    <td>{{ $s->salle ?? '-' }}</td>
                    <td>{{ $s->student->nom ?? '-' }}</td>
                    <td>{{ $s->student->prenom ?? '-' }}</td>
                    <td>{{ $s->student->filiere ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@endsection