@extends('layouts.app')

@section('title', 'Exporter')

@section('content')

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    {{-- Export Planning --}}
    <h2>Exporter le planning des soutenances</h2>
    <p style="color: gray; font-size: 14px;">Planning complet : date, heure, salle, étudiant, encadrant, jury</p>
    <a href="{{ route('export.pdf') }}">Exporter en PDF</a>
    &nbsp;&nbsp;
    <a href="{{ route('export.word') }}">Exporter en Word</a>

    <hr>

    {{-- Export Affectation Encadrants --}}
    <h2>Exporter l'affectation des encadrants</h2>
    <p style="color: gray; font-size: 14px;">Liste des encadrants avec les étudiants assignés</p>
    <a href="{{ route('export.affectation.pdf') }}">Exporter en PDF</a>
    &nbsp;&nbsp;
    <a href="{{ route('export.affectation.word') }}">Exporter en Word</a>

    <hr>

    {{--Export PV individuels --}}
    <h2>Export PV individuels</h2>

    {{-- Barre de recherche --}}
    <br>

    <input type="text" id="recherche" placeholder="Chercher un étudiant..."
       style="width:300px; padding:5px; margin-bottom:10px;"
       onkeyup="filtrerEtudiants()">

    <table border="1" cellpadding="6" cellspacing="0" id="tableau-pv">
        <thead>
            <tr>
                <th>Étudiant</th>
                <th>Filière</th>
                <th>Date</th>
                <th>Salle</th>
                <th>Encadrant</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($soutenances as $s)
            <tr class="ligne-etudiant">
                <td>{{ $s->student->nom ?? '' }} {{ $s->student->prenom ?? '' }}</td>
                <td>{{ $s->student->filiere ?? '' }}</td>
                <td>{{ $s->date_soutenance ?? 'Non planifiée' }}</td>
                <td>{{ $s->salle ?? '-' }}</td>
                <td>{{ $s->student->encadrant->nom ?? '-' }}</td>
                <td>
                    <a href="{{ route('export.pv', ['id' => $s->id, 'format' => 'pdf']) }}">PDF</a>
                    &nbsp;|&nbsp;
                    <a href="{{ route('export.pv', ['id' => $s->id, 'format' => 'word']) }}">Word</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <br>
    <script>
        function filtrerEtudiants() {
            const input  = document.getElementById('recherche').value.toLowerCase();
            const lignes = document.querySelectorAll('.ligne-etudiant');

            lignes.forEach(function(ligne) {
                const texte = ligne.textContent.toLowerCase();
                ligne.style.display = texte.includes(input) ? '' : 'none';
            });
        }
    </script>

    

@endsection