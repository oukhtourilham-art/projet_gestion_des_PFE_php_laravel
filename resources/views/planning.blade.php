@extends('layouts.app')

@section('title', 'Planning des soutenances')

@section('content')

    <h2>Planning des soutenances</h2>

    @if($soutenances->isEmpty())
        <p>Aucune soutenance planifiée pour le moment.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Salle</th>
                    <th>Jury</th>
                </tr>
            </thead>
            <tbody>
                @foreach($soutenances as $s)
                <tr>
                    <td>{{ $s->student->prenom }} {{ $s->student->nom }}</td>
                    <td>{{ $s->date }}</td>
                    <td>{{ $s->heure }}</td>
                    <td>{{ $s->salle }}</td>
                    <td>
                        @foreach($s->juries as $j)
                            {{ $j->professor->nom }}<br>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

@endsection