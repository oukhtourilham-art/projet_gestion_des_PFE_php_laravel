@extends('layouts.app')

@section('title', 'Exporter')

@section('content')

    <h2>Exporter le planning</h2>

    <a href="{{ route('export.pdf') }}">Exporter en PDF</a>
    <a href="{{ route('export.word') }}">Exporter en Word</a>

    <h2>Procès-Verbaux individuels</h2>

    <table>
        <thead>
            <tr>
                <th>Étudiant</th>
                <th>Date</th>
                <th>Salle</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($soutenances as $s)
            <tr>
                <td>{{ $s->student->prenom }} {{ $s->student->nom }}</td>
                <td>{{ $s->date }}</td>
                <td>{{ $s->salle }}</td>
                <td>
                    <a href="{{ route('export.pv', $s->id) }}">Télécharger PV</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

@endsection