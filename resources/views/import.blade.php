@extends('layouts.app')

@section('title', 'Importer les données')

@section('content')

    @if($errors->any())
        <ul>
            @foreach($errors->all() as $error)
                <li style="color:red;">{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    @if(session('success'))
        <p style="color: green;">✅ {{ session('success') }}</p>
    @endif

    @if(session('error'))
        <p style="color: red;">❌ {{ session('error') }}</p>
    @endif

    {{-- Import Étudiants --}}
    <h2>Import Étudiants</h2>
    <p style="color:gray; font-size:13px;">
        Fichier Excel avec <strong>3 feuilles</strong> :
        Feuille 1 = GI | Feuille 2 = DATA | Feuille 3 = TDAI<br>
        Colonnes : CNE, Nom, Prénom, Email personnel,
        Email académique, Sujet, Langue (FR/EN), Binôme (oui/non)
    </p>
    <form action="{{ route('import.students') }}" method="POST"
          enctype="multipart/form-data">
        @csrf
        <label>Choisir le fichier Excel :</label>
        <input type="file" name="excel_file" accept=".xlsx,.xls" required>
        <br><br>
        <button type="submit">Importer les étudiants</button>
    </form>

    <hr>

    {{-- Import Professeurs --}}
    <h2>Import Professeurs</h2>
    <p style="color:gray; font-size:13px;">
        Colonnes : Nom, Prénom, Discipline
    </p>
    <form action="{{ route('import.professors') }}" method="POST"
          enctype="multipart/form-data">
        @csrf
        <label>Choisir le fichier Excel :</label>
        <input type="file" name="excel_file" accept=".xlsx,.xls" required>
        <br><br>
        <button type="submit">Importer les professeurs</button>
    </form>

    <hr>

    {{-- Dates de soutenance --}}
    <h2>Dates des soutenances</h2>
    <p style="color:gray; font-size:13px;">
        Entrez la date de début et la date de fin —
        le programme calcule automatiquement le nombre de jours
    </p>
    <form action="{{ route('planning.dates') }}" method="POST">
        @csrf
        <label>Date de début :</label>
        <input type="date" name="date_debut" required
               value="{{ session('date_debut') }}">
        <br><br>

        <label>Date de fin :</label>
        <input type="date" name="date_fin" required
               value="{{ session('date_fin') }}">
        <br><br>

        {{-- Calcul automatique affiché avant soumission --}}
        <p id="duree_info" style="color:gray; font-size:13px;"></p>

        <button type="submit">Enregistrer les dates</button>
    </form>

    {{-- Afficher les dates enregistrées --}}
    @if(session('date_debut') && session('date_fin'))
    <p style="color:green; font-size:13px;">
        ✅ Dates enregistrées :
        {{ \Carbon\Carbon::parse(session('date_debut'))->format('d/m/Y') }}
        →
        {{ \Carbon\Carbon::parse(session('date_fin'))->format('d/m/Y') }}
        ({{ \Carbon\Carbon::parse(session('date_debut'))->diffInDays(\Carbon\Carbon::parse(session('date_fin'))) + 1 }} jours)
    </p>
    @endif

    <hr>

    {{-- Salles de soutenance --}}
    <h2>Salles de soutenance</h2>
    <p style="color:gray; font-size:13px;">
        Cochez les salles à utiliser pour les soutenances
    </p>

    <form action="{{ route('import.salles') }}" method="POST">
        @csrf
        @foreach($sallesDisponibles as $salle)
        <label>
            <input type="checkbox" name="salles[]" value="{{ $salle }}"
                {{ in_array($salle, $sallesSelectionnees) ? 'checked' : '' }}>
            {{ $salle }}
        </label><br>
        @endforeach
        <br>
        <button type="submit" id="btn-salles">Enregistrer les salles</button>
    </form>

    <br>

    <form action="{{ route('import.salle.add') }}" method="POST">
        @csrf
        <label>Ajouter une nouvelle salle :</label>
        <input type="text" name="nouvelle_salle"
               placeholder="Ex: Salle 10 AB" style="width:200px;">
        <button type="submit">Ajouter</button>
    </form>

    @if(session('salles'))
    <br>
    <p><strong>Salles sélectionnées :</strong>
        {{ implode(', ', session('salles')) }}
    </p>
    @endif

    
    <p id="info-salles" style="font-size:13px; margin-top:5px;"></p>

    <script>
        // Calcul en temps réel des créneaux
        const nbJours      = {{ count(session('jours_soutenance', [])) }};
        const nbEtudiants  = {{ \App\Models\Student::count() }};

        function verifierSalles() {
            const cases      = document.querySelectorAll('input[name="salles[]"]:checked');
            const nbSalles   = cases.length;
            const info       = document.getElementById('info-salles');
            const btnSalles  = document.getElementById('btn-salles');

            if (nbJours == 0) {
                info.textContent = '⚠️ Enregistrez d\'abord les dates de soutenance.';
                info.style.color = 'orange';
                btnSalles.disabled = true;
                return;
            }

            if (nbSalles == 0) {
                info.textContent = '';
                btnSalles.disabled = false;
                return;
            }

            const totalCreneaux    = 5 * nbSalles * nbJours;
            const sallesNecessaires = Math.ceil(nbEtudiants / (5 * nbJours));

            if (totalCreneaux < nbEtudiants) {
                info.textContent = '❌ Insuffisant ! ' + nbSalles + ' salle(s) × 5 créneaux × ' +
                           nbJours + ' jour(s) = ' + totalCreneaux + ' créneaux pour ' +
                           nbEtudiants + ' étudiants. Minimum : ' + sallesNecessaires + ' salle(s).';
                info.style.color = 'red';
                btnSalles.disabled = true;
            }else {
                const restants = totalCreneaux - nbEtudiants;
                info.textContent = '✅ Suffisant ! ' + totalCreneaux + ' créneaux pour ' +
                           nbEtudiants + ' étudiants (' + restants + ' libre(s)).';
                info.style.color = 'green';
                btnSalles.disabled = false;
            }
        }

        // Écouter les changements de cases à cocher
        document.querySelectorAll('input[name="salles[]"]')
            .forEach(cb => cb.addEventListener('change', verifierSalles));

        // Lancer au chargement
        verifierSalles();
    </script>

    {{-- Script calcul automatique durée --}}
    <script>
        const debut = document.querySelector('[name="date_debut"]');
        const fin   = document.querySelector('[name="date_fin"]');
        const info  = document.getElementById('duree_info');

        function calculerDuree() {
            if (debut.value && fin.value) {
                const d1 = new Date(debut.value);
                const d2 = new Date(fin.value);
                const diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
                if (diff > 0) {
                    info.textContent = '→ Durée calculée : ' + diff + ' jour(s) de soutenance';
                    info.style.color = 'green';
                } else {
                    info.textContent = '⚠️ La date de fin doit être après la date de début';
                    info.style.color = 'red';
                }
            }
        }

        debut.addEventListener('change', calculerDuree);
        fin.addEventListener('change', calculerDuree);
    </script>

@endsection