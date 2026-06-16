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

    {{-- Import unifié étudiants + profs --}}
    <h2>Importer les données (étudiants + professeurs)</h2>
    <p style="color:gray; font-size:13px;">
        Un seul fichier Excel suffit : chaque onglet (sauf "profs") est une filière —
        le nom de l'onglet devient automatiquement le nom de la filière.
    </p>

    @if($filieres->isNotEmpty())
    <p style="color:#555; font-size:13px;">
        📂 Filières actuellement en base :
        @foreach($filieres as $f)
            <strong>{{ $f }}</strong>{{ !$loop->last ? ', ' : '' }}
        @endforeach
    </p>
    @endif

    <form action="{{ route('import.unified') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label>Fichier Excel (.xlsx) :</label><br>
        <input type="file" name="fichier" accept=".xlsx,.xls" required style="margin:6px 0;">
        <br><br>
        <button type="submit">📥 Importer</button>
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
               value="{{ old('date_debut', session('date_debut', $planningConfig?->date_debut ?? '')) }}">
        <br><br>

        <label>Date de fin :</label>
        <input type="date" name="date_fin" required
               value="{{ old('date_fin', session('date_fin', $planningConfig?->date_fin ?? '')) }}">
        <br><br>

        <label>Heure de début :</label>
        <input type="time" name="start_time" required
               value="{{ old('start_time', $planningConfig ? \Carbon\Carbon::parse($planningConfig->start_time)->format('H:i') : '09:00') }}">
        <br><br>

        <label>Heure de fin :</label>
        <input type="time" name="end_time" required
               value="{{ old('end_time', $planningConfig ? \Carbon\Carbon::parse($planningConfig->end_time)->format('H:i') : '17:00') }}">
        <br><br>

        <label>Durée d'une soutenance (minutes) :</label>
        <select name="slot_duration_minutes" required>
            @foreach([60, 75, 90, 105, 120] as $mins)
                <option value="{{ $mins }}"
                    {{ (old('slot_duration_minutes', $planningConfig?->slot_duration_minutes ?? 60) == $mins) ? 'selected' : '' }}>
                    {{ $mins }} min
                </option>
            @endforeach
        </select>
        <br><br>

        <label>Durée de pause entre créneaux (minutes) :</label>
        <select name="break_duration_minutes" required>
            @foreach([0, 10, 15, 20, 30, 45] as $mins)
                <option value="{{ $mins }}"
                    {{ (old('break_duration_minutes', $planningConfig?->break_duration_minutes ?? 0) == $mins) ? 'selected' : '' }}>
                    {{ $mins }} min{{ $mins === 0 ? ' (sans pause)' : '' }}
                </option>
            @endforeach
        </select>
        <br><br>

        <p id="duree_info" style="color:gray; font-size:13px;"></p>

        <button type="submit">Enregistrer les dates</button>
    </form>

    @if(session('date_debut') && session('date_fin'))
    <p style="color:green; font-size:13px;">
        ✅ Dates enregistrées :
        {{ \Carbon\Carbon::parse(session('date_debut'))->format('d/m/Y') }}
        →
        {{ \Carbon\Carbon::parse(session('date_fin'))->format('d/m/Y') }}
        ({{ \Carbon\Carbon::parse(session('date_debut'))->diffInDays(\Carbon\Carbon::parse(session('date_fin'))) + 1 }} jours)
    </p>
    @endif

    @if($planningConfig && !empty($planningConfig->time_slots))
    <p style="color:gray; font-size:13px;">
        Créneaux générés :
        @foreach($planningConfig->time_slots as $slot)
            {{ $slot['debut'] }}–{{ $slot['fin'] }}{{ !$loop->last ? ', ' : '' }}
        @endforeach
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
        const nbJours      = {{ count(session('jours_soutenance', [])) }};
        const nbEtudiants  = {{ \App\Models\Student::count() }};
        const nbCreneaux   = {{ $nbCreneaux }};

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

            const totalCreneaux    = nbCreneaux * nbSalles * nbJours;
            const sallesNecessaires = Math.ceil(nbEtudiants / (nbCreneaux * nbJours));

            if (totalCreneaux < nbEtudiants) {
                info.textContent = '❌ Insuffisant ! ' + nbSalles + ' salle(s) × ' + nbCreneaux + ' créneaux × ' +
                           nbJours + ' jour(s) = ' + totalCreneaux + ' créneaux pour ' +
                           nbEtudiants + ' étudiants. Minimum : ' + sallesNecessaires + ' salle(s).';
                info.style.color = 'red';
                btnSalles.disabled = true;
            } else {
                const restants = totalCreneaux - nbEtudiants;
                info.textContent = '✅ Suffisant ! ' + totalCreneaux + ' créneaux pour ' +
                           nbEtudiants + ' étudiants (' + restants + ' libre(s)).';
                info.style.color = 'green';
                btnSalles.disabled = false;
            }
        }

        document.querySelectorAll('input[name="salles[]"]')
            .forEach(cb => cb.addEventListener('change', verifierSalles));

        verifierSalles();
    </script>

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