@extends('layouts.app')

@section('title', 'Importer les données')

@section('content')

    @if($errors->any())
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif

    {{-- Import Étudiants --}}
    <h2>Import Étudiants</h2>
    <form action="{{ route('import.students') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <label for="filiere">Filière :</label>
        <select id="filiere" name="filiere" required>
            <option value=""> Choisir la filière </option>
            <option value="TDAI">TDAI</option>
            <option value="GI">GI</option>
            <option value="DATA">Data</option>
        </select>
        <br><br>

        <label for="excel_students">Choisir un fichier Excel :</label>
        <input type="file" id="excel_students" name="excel_file" accept=".xlsx,.xls" required>
        <br><br>

        <button type="submit">Importer les étudiants</button>
    </form>

    <hr>

    {{-- Import Professeurs --}}
    <h2>Import Professeurs</h2>
    <form action="{{ route('import.professors') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <label for="excel_professors">Choisir un fichier Excel :</label>
        <input type="file" id="excel_professors" name="excel_file" accept=".xlsx,.xls" required>
        <br><br>

        <button type="submit">Importer les professeurs</button>
    </form>

    <hr>

    {{-- Import Sujets PFE --}}
    <h2>Import Sujets PFE</h2>
    <p style="color: gray; font-size: 14px;">Colonnes attendues : CNE, Nom, Sujet, Langue, Binôme</p>
    <form action="{{ route('import.sujets') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <label for="excel_sujets">Choisir un fichier Excel :</label>
        <input type="file" id="excel_sujets" name="excel_file" accept=".xlsx,.xls" required>
        <br><br>

        <button type="submit">Importer les sujets PFE</button>
    </form>

    <hr>

    {{-- Dates de soutenance --}}
    <h2>Dates des soutenances</h2>
    <form action="{{ route('planning.dates') }}" method="POST">
        @csrf
        <input type="date" id="date" name="date_soutenance" required>
        <br><br>

        <button type="submit">Enregistrer les dates</button>
    </form>

@endsection