<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV — {{ $soutenance->student->prenom }} {{ $soutenance->student->nom }}</title>
    <link rel="stylesheet" href="pv.css">
</head>
<body>

    <!--Header-->
    <div class="header">
        <div class="header-logo-left">
            <img src="logo-university.png" alt="Logo Université">
        </div>
        <div class="header-center">
            <p class="univ-name">UNIVERSITE ABDELMALEK ESSAADI</p>
            <p class="univ-sub">École Nationale des Sciences Appliquées d'Al-Hoceima - Maroc</p>
            <h1>Département de Mathématiques et Informatique</h1>
            <p class="doc-type">Fiche d'évaluation du Projet de Fin d'Étude</p>
            <p class="doc-year">Année Universitaire : 2023-2024</p>
        </div>
        <div class="header-logo-right">
            <img src="logo-ensa.png" alt="Logo ENSA">
        </div>
    </div>

    <hr class="header-line">

    <!-- Body-->
    <div class="body">

        <!-- Nom -->
        <div class="field-row">
            <span class="field-label">Nom - Prénom de l'élève ingénieur :</span>
            <span class="field-value">{{ $soutenance->student->prenom }} {{ $soutenance->student->nom }}</span>
            <span class="field-dots"></span>
        </div>

        <!-- Filière -->
        <div class="field-row filiere-row">
            <span class="field-label">Filière :</span>
            <span class="checkbox-group">
                <span class="checkbox {{ $soutenance->student->filiere === 'Ingénierie des Données' ? 'checked' : '' }}"></span>
                <span class="checkbox-label">Ingénierie des Données</span>
                <span class="checkbox {{ $soutenance->student->filiere === 'Génie Informatique' ? 'checked' : '' }}"></span>
                <span class="checkbox-label">Génie Informatique</span>
                <span class="checkbox {{ $soutenance->student->filiere === 'Transformation Digitale Et  Intellegence Artificielle' ? 'checked' : '' }}"></span>
                <span class="checkbox-label">Transformation Digitale Et  Intellegence Artificielle</span>
            </span>
        </div>

        <!-- Intitulé du rapport -->
        <div class="field-block">
            <p class="field-label underline">Intitulé du rapport :</p>
            <div class="field-line">{{ $soutenance->sujet }}</div>
        </div>

        <!-- Encadrant -->
        <div class="field-block">
            <p class="field-label underline">L'encadrant (e) interne:</p>
            <div class="field-sub-row">
                <span class="sub-label">Pr.</span>
                <span class="field-line-inline">{{ $soutenance->encadrant->nom ?? '' }}</span>
            </div>
        </div>

        <!-- Membres du jury -->
        <div class="field-block">
            <p class="field-label underline">Membres du jury :</p>
            @foreach($soutenance->juries as $i => $j)
            <div class="jury-row">
                <span class="jury-dash">–</span>
                <span class="sub-label">Pr.</span>
                <span class="field-line-inline">{{ $j->professor->nom }}</span>
                <span class="jury-role">
                    {{ $i === 0 ? 'Président' : 'Rapporteur' }}
                </span>
            </div>
            @endforeach
        </div>

        <!-- Note du Contenu -->
        <div class="field-block note-block">
            <p class="field-label underline">
                Note du Contenu
                <span class="note-hint">(En prenant en compte l’appréciation de l’entreprise)</span>
            </p>
            <div class="note-row">
                <span class="note-letter">C =</span>
                <span class="note-box">{{ $soutenance->note_contenu ?? '' }}</span>
            </div>
        </div>

        <!-- Note du Mémoire -->
        <div class="field-block note-block">
            <p class="field-label underline">Note du Mémoire</p>
            <div class="note-row">
                <span class="note-letter">M =</span>
                <span class="note-box">{{ $soutenance->note_rapport ?? '' }}</span>
            </div>
        </div>

        <!-- Note de la Soutenance -->
        <div class="field-block note-block">
            <p class="field-label underline">Note de la Soutenance</p>
            <div class="note-row">
                <span class="note-letter">S =</span>
                <span class="note-box">{{ $soutenance->note_expose ?? '' }}</span>
            </div>
        </div>

        <!-- Moyenne -->
        <div class="moyenne-box">
            <p class="moyenne-title">MOYENNE</p>
            <p class="moyenne-formula">
                Moyenne &nbsp;= C * 0,5 + M * 0,2 + S * 0,3 =
                <span class="moyenne-result">
                    @php
                        $c = $soutenance->note_contenu ?? 0;
                        $m = $soutenance->note_rapport ?? 0;
                        $s = $soutenance->note_expose ?? 0;
                        $moy = ($c * 0.5) + ($m * 0.2) + ($s * 0.3);
                    @endphp
                    {{ number_format($moy, 2) }}
                </span>
            </p>
        </div>

        <!-- Date et signatures -->
        <div class="signatures-section">
            <div class="date-row">
                <span class="field-label">Le :</span>
                <span class="field-dots-sm"></span>
                <span>{{ \Carbon\Carbon::parse($soutenance->date)->format('d/m/Y') }}</span>
            </div>

            <p class="sig-title">Signature des membres du jury :</p>
            <div class="sig-row">
                @foreach($soutenance->juries as $j)
                <div class="sig-block">
                    <span class="sub-label">Pr.</span>
                    <span class="sig-dots"></span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

</body>
</html>