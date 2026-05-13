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
        <p>{{ session('success') }}</p>
    @endif

    <h2>Import Étudiants</h2>
    <form action="{{ route('import.students') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="excel_students">Choisir un fichier Excel :</label>
        <input type="file" id="excel_students" name="excel_file" accept=".xlsx,.xls" required>
        <select name="filiere" required>
            <option value="ID">ID</option>
            <option value="TDIA">TDIA</option>
            <option value="GI">GI</option>
        </select>
        <button type="submit">Importer les étudiants</button>
    </form>

    <hr>

    <h2>Import Professeurs</h2>
    <form action="{{ route('import.professors') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="excel_professors">Choisir un fichier Excel :</label>
        <input type="file" id="excel_professors" name="excel_file" accept=".xlsx,.xls" required>
        <button type="submit">Importer les professeurs</button>
    </form>

@endsection