<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV — {{ $soutenance->student->prenom }} {{ $soutenance->student->nom }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .header img { width: 80px; height: 80px; object-fit: contain; }
        .header-center { text-align: center; flex: 1; padding: 0 20px; }
        .univ-name { font-weight: bold; font-size: 13px; margin: 0; }
        .univ-sub { font-size: 11px; margin: 2px 0; }
        .doc-type { font-weight: bold; font-size: 13px; margin: 5px 0; }
        .doc-year { font-size: 11px; margin: 0; }
        .header-line { border: 2px solid black; margin: 10px 0; }

        /* Champs */
        .field-row { margin: 8px 0; }
        .field-label { font-weight: bold; }
        .field-value { margin-left: 5px; }
        .field-block { margin: 12px 0; }
        .field-line { border-bottom: 1px solid black; min-height: 20px; margin-top: 4px; padding: 2px; }
        .field-sub-row { display: flex; align-items: center; gap: 5px; margin-top: 4px; }
        .field-line-inline { border-bottom: 1px solid black; flex: 1; min-height: 18px; }
        .sub-label { font-weight: bold; white-space: nowrap; }
        .underline { text-decoration: underline; margin-bottom: 4px; }

        /* Jury */
        .jury-row { display: flex; align-items: center; gap: 8px; margin: 4px 0; }
        .jury-role { font-style: italic; color: #444; }

        /* Notes */
        .note-row { display: flex; align-items: center; gap: 10px; margin-top: 4px; }
        .note-box { border: 1px solid black; width: 60px; height: 22px; display: inline-block; }

        /* Moyenne */
        .moyenne-box { border: 2px solid black; padding: 8px; margin: 15px 0; text-align: center; }
        .moyenne-title { font-weight: bold; font-size: 14px; margin: 0 0 5px 0; }

        /* Signatures */
        .signatures-section { margin-top: 20px; }
        .sig-row { display: flex; gap: 30px; margin-top: 10px; }
        .sig-block { display: flex; flex-direction: column; align-items: center; min-width: 150px; }
        .sig-name { font-size: 11px; margin-bottom: 30px; }
        .sig-line { border-bottom: 1px solid black; width: 100%; margin-top: 5px; }
        ..header {
    width: 100%;
    position: relative;
    text-align: center;
    margin-bottom: 20px;
}

.logo-left {
    position: absolute;
    left: 0;
    top: 0;
}

.logo-right {
    position: absolute;
    right: 0;
    top: 0;
}

.logo-left img,
.logo-right img {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

.header-center {
    padding: 0 100px;
}
    </style>
</head>
<body>

    <!-- Header avec logos -->
    <div class="header">

    <!-- Logo gauche -->
    <div class="logo-left">
        <img src="{{ public_path('logo-university.png') }}" alt="Logo Université">
    </div>

    <!-- Texte centre -->
    <div class="header-center">
        <p class="univ-name">UNIVERSITE ABDELMALEK ESSAADI</p>
        <p class="univ-sub">École Nationale des Sciences Appliquées d'Al-Hoceima</p>
        <h3 style="margin: 4px 0;">Département Mathématiques Informatique</h3>
        <p class="doc-type">Fiche d'évaluation du Projet de Fin d'Étude</p>
        <p class="doc-year">Année Universitaire : 2025-2026</p>
    </div>

    <!-- Logo droite -->
    <div class="logo-right">
        <img src="{{ public_path('logo-ensa.png') }}" alt="Logo ENSA">
    </div>

</div>

    <hr class="header-line">

    <!-- Nom étudiant -->
    <div class="field-row">
        <span class="field-label">Nom - Prénom de l'élève ingénieur :</span>
        <span class="field-value">{{ $soutenance->student->prenom }} {{ $soutenance->student->nom }}</span>
    </div>

    <!-- Filière -->
    <div class="field-row">
        <span class="field-label">Filière :</span>
        <span class="field-value">{{ $soutenance->student->filiere }}</span>
    </div>

    <!-- Intitulé du rapport -->
    <div class="field-block">
        <p class="field-label underline">Intitulé du rapport :</p>
        <div class="field-line">{{ $soutenance->sujet ?? '' }}</div>
    </div>

    <!-- Encadrant -->
    <div class="field-block">
        <p class="field-label underline">L'encadrant(e) interne :</p>
        <div class="field-sub-row">
            <span class="sub-label">Pr.</span>
    
            <span class="field-line-inline">
                {{ $soutenance->student->encadrant->nom ?? '' }}
                {{ $soutenance->student->encadrant->prenom ?? '' }}
            </span>
        </div>
    </div>

    <!-- Membres du jury -->
    <div class="field-block">
        <p class="field-label underline">Membres du jury :</p>

        {{-- Encadrant ajouté comme 1er membre --}}
        <div class="jury-row">
            <span>–</span>
            <span class="sub-label">Pr.</span>
            <span class="field-line-inline">
                {{ $soutenance->student->encadrant->nom ?? '' }}
                {{ $soutenance->student->encadrant->prenom ?? '' }}
            </span>
            <span class="jury-role">(Encadrant)</span>
        </div>

        {{-- Les 2 membres du jury --}}
        @foreach($soutenance->juries as $i => $j)
        <div class="jury-row">
            <span>–</span>
            <span class="sub-label">Pr.</span>
            <span class="field-line-inline">
                {{ $j->professor->nom ?? '' }}
                {{ $j->professor->prenom ?? '' }}
            </span>
            <span class="jury-role">{{ $i === 0 ? '(Président)' : '(Rapporteur)' }}</span>
        </div>
        @endforeach
    </div>

    <!-- Note du Contenu -->
    <div class="field-block">
        <p class="field-label underline">
            Note du Contenu
            <span style="font-weight: normal; font-style: italic;">(En prenant en compte l'appréciation de l'entreprise)</span>
        </p>
        <div class="note-row">
            <span class="sub-label">C =</span>
            <span class="note-box"></span>
        </div>
    </div>

    <!-- Note du Mémoire -->
    <div class="field-block">
        <p class="field-label underline">Note du Mémoire</p>
        <div class="note-row">
            <span class="sub-label">M =</span>
            <span class="note-box"></span>
        </div>
    </div>

    <!-- Note de la Soutenance -->
    <div class="field-block">
        <p class="field-label underline">Note de la Soutenance</p>
        <div class="note-row">
            <span class="sub-label">S =</span>
            <span class="note-box"></span>
        </div>
    </div>

    <!-- Moyenne -->
    <div class="moyenne-box">
        <p class="moyenne-title">MOYENNE</p>
        <p>Moyenne = C × 0,5 + M × 0,2 + S × 0,3 = ___________</p>
    </div>

    <!-- Date et signatures -->
    <div class="signatures-section">
        <div class="field-row">
            <span class="field-label">Le :</span>
            <span style="margin-left: 5px;">
                {{ $soutenance->date_soutenance ? \Carbon\Carbon::parse($soutenance->date_soutenance)->format('d/m/Y') : '___/___/______' }}
            </span>
        </div>

        <p class="field-label" style="margin-top: 15px;">Signature des membres du jury :</p>

        {{--3 signatures : encadrant + 2 jurys --}}
        <div class="sig-row">

            {{-- Encadrant --}}
            <div class="sig-block">
                <span class="sig-name">
                    Pr. {{ $soutenance->student->encadrant->nom ?? '' }}
                    {{ $soutenance->student->encadrant->prenom ?? '' }}
                    <br><small>(Encadrant)</small>
                </span>
                <div class="sig-line"></div>
            </div>

            {{-- 2 membres jury --}}
            @foreach($soutenance->juries as $i => $j)
            <div class="sig-block">
                <span class="sig-name">
                    Pr. {{ $j->professor->nom ?? '' }}
                    {{ $j->professor->prenom ?? '' }}
                    <br><small>{{ $i === 0 ? '(Président)' : '(Rapporteur)' }}</small>
                </span>
                <div class="sig-line"></div>
            </div>
            @endforeach

        </div>
    </div>

</body>
</html>