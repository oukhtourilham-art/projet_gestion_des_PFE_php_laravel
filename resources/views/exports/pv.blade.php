@php
if (isset($soutenance)) {
    if (!isset($logo_universite)) {
        $logoUniversitePath = public_path('images/LogoUniversite.png');
        $logo_universite = file_exists($logoUniversitePath) ? base64_encode(file_get_contents($logoUniversitePath)) : null;
    }
    if (!isset($logo_ensah)) {
        $logoEnsahPath = public_path('images/LogoEnsah.png');
        $logo_ensah = file_exists($logoEnsahPath) ? base64_encode(file_get_contents($logoEnsahPath)) : null;
    }
    if (!isset($annee_universitaire)) {
        $annee_universitaire = '2025-2026';
    }
    if (!isset($nom_prenom_etudiant)) {
        $nom_prenom_etudiant = ($soutenance->student->prenom ?? '') . ' ' . ($soutenance->student->nom ?? '');
        if (isset($soutenance->binome_student_id)) {
            $binome = \App\Models\Student::find($soutenance->binome_student_id);
            if ($binome) {
                $nom_prenom_etudiant .= ' / ' . ($binome->prenom ?? '') . ' ' . ($binome->nom ?? '');
            }
        }
    }
    if (!isset($filiere)) {
        $filiere = $soutenance->student->filiere ?? '';
    }
    if (!isset($intitule_rapport)) {
        $intitule_rapport = $soutenance->student->sujet ?? '';
    }
    if (!isset($encadrant_interne)) {
        $encadrant_interne = ($soutenance->student->encadrant->nom ?? '') . ' ' . ($soutenance->student->encadrant->prenom ?? '');
    }
    if (!isset($jury_president)) {
        $jury_president = ($soutenance->student->encadrant->nom ?? '') . ' ' . ($soutenance->student->encadrant->prenom ?? '');
    }
    if (!isset($jury_president_court)) {
        $jury_president_court = $soutenance->student->encadrant->nom ?? '';
    }
    if (!isset($jury_rapporteur1)) {
        $jury_rapporteur1 = isset($soutenance->juries[0]->professor) ? ($soutenance->juries[0]->professor->nom . ' ' . $soutenance->juries[0]->professor->prenom) : '';
    }
    if (!isset($jury_rapporteur1_court)) {
        $jury_rapporteur1_court = isset($soutenance->juries[0]->professor) ? $soutenance->juries[0]->professor->nom : '';
    }
    if (!isset($jury_rapporteur2)) {
        $jury_rapporteur2 = isset($soutenance->juries[1]->professor) ? ($soutenance->juries[1]->professor->nom . ' ' . $soutenance->juries[1]->professor->prenom) : '';
    }
    if (!isset($jury_rapporteur2_court)) {
        $jury_rapporteur2_court = isset($soutenance->juries[1]->professor) ? $soutenance->juries[1]->professor->nom : '';
    }
    if (!isset($date_soutenance)) {
        $date_soutenance = $soutenance->date_soutenance ? \Carbon\Carbon::parse($soutenance->date_soutenance)->format('d/m/Y') : null;
    }
}
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fiche d'évaluation PFE</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            color: #000;
            background: #fff;
        }

        @page { size: A4; margin: 2cm 2.5cm 2cm 2.5cm; }

        .page { width: 100%; }

        /* EN-TÊTE */
        .header-row {
            width: 100%;
            border-collapse: collapse;
        }
        .header-row td { vertical-align: middle; }
        .header-center { text-align: center; font-size: 11pt; font-weight: bold; }
        .header-sub { text-align: center; font-size: 10pt; margin-top: 3px; }

        /* TITRES */
        .dept { text-align: center; font-size: 13pt; font-weight: bold; margin: 14px 0 2px 0; }
        .titre { text-align: center; font-size: 12pt; margin: 2px 0; }
        .annee { text-align: center; font-size: 12pt; margin: 2px 0 14px 0; }

        /* CORPS */
        .label {
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 10px 0 3px 0;
        }
        .label-note { font-weight: normal; text-decoration: none; font-style: italic; font-size: 10pt; }
        .value { font-size: 12pt; margin: 3px 0 8px 18px; }
        .filiere-line { font-size: 12pt; font-weight: bold; margin: 6px 0 10px 0; }

        /* TABLEAU JURY */
        .table-jury { width: 100%; border-collapse: collapse; margin: 6px 0 10px 0; }
        .table-jury td { border: 1px solid #000; padding: 5px 10px; font-size: 12pt; }

        /* NOTES */
        .note { font-size: 12pt; margin: 4px 0; }

        /* TABLEAU MOYENNE */
        .table-moy { width: 100%; border-collapse: collapse; margin: 10px 0 14px 0; }
        .table-moy th {
            border: 1px solid #000; padding: 5px 10px;
            text-align: center; font-size: 12pt; font-weight: bold;
        }
        .table-moy td { border: 1px solid #000; padding: 6px 10px; font-size: 12pt; }

        /* DATE / SIGNATURES */
        .date { font-size: 12pt; margin: 14px 0 8px 0; }
        .sig-title { font-size: 12pt; margin-bottom: 40px; }
        .table-sig { width: 100%; border-collapse: collapse; }
        .table-sig td { width: 33.33%; text-align: center; font-size: 12pt; padding: 0 5px; vertical-align: top; }
    </style>
</head>
<body>
<div class="page">

    {{-- EN-TÊTE --}}
    <table class="header-row">
        <tr>
            <td style="width:90px; text-align:left;">
                @if($logo_universite)
                    <img src="data:image/png;base64,{{ $logo_universite }}" width="80" alt="">
                @endif
            </td>
            <td class="header-center">
                UNIVERSITE ABDELMALEK ESSAADI
                <div class="header-sub">Ecole Nationale des Sciences Appliquées d'Al-Hoceima - Maroc</div>
            </td>
            <td style="width:90px; text-align:right;">
                @if($logo_ensah)
                    <img src="data:image/png;base64,{{ $logo_ensah }}" width="80" alt="">
                @endif
            </td>
        </tr>
    </table>

    {{-- TITRES --}}
    <p class="dept">Département de Mathématiques et Informatique</p>
    <p class="titre">Fiche d'évaluation du Projet de Fin d'Étude</p>
    <p class="annee">Année Universitaire : {{ $annee_universitaire }}</p>

    {{-- NOM ETUDIANT --}}
    <p class="label">Nom - Prénom de l'élève ingénieur :</p>
    <p class="value">• {{ $nom_prenom_etudiant ?? '……………………………………………………………………….' }}</p>

    {{-- FILIERE : affiche la filière de l'étudiant soulignée --}}
    @php
        $filiereMap = [
            'DATA' => 'Ingénierie des Données',
            'GI'   => 'Génie Informatique',
            'TDIA' => 'Technologies et Développement IA',
        ];
        $labels = [];
        foreach ($filiereMap as $code => $label) {
            if (isset($filiere) && strtoupper($filiere) === $code) {
                $labels[] = '<u><strong>' . $label . '</strong></u>';
            } else {
                $labels[] = $label;
            }
        }
        // Si filière inconnue, juste afficher le nom brut souligné
        if (isset($filiere) && !array_key_exists(strtoupper($filiere), $filiereMap)) {
            $labels = ['<u><strong>' . $filiere . '</strong></u>'];
        }
    @endphp
    <p class="filiere-line">
        <strong><u>Filière</u> :</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        {!! implode('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $labels) !!}
    </p>

    {{-- INTITULE RAPPORT --}}
    <p class="label">Intitulé du rapport :</p>
    <p class="value">• {{ $intitule_rapport ?? '……………………………………………………………………….' }}</p>

    {{-- ENCADRANT INTERNE --}}
    <p class="label">L'encadrant (e) interne :</p>
    <p class="value">• Pr.&nbsp;&nbsp;{{ $encadrant_interne ?? '……………………………………………………………………….' }}</p>

    {{-- MEMBRES DU JURY
         Président = encadrant, Rapporteurs = membres du jury --}}
    <p class="label">Membres du jury :</p>
    <table class="table-jury">
        <tr>
            <td>Pr.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $jury_president ?? '…………………………………………………' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Président</td>
        </tr>
        <tr>
            <td>
                Pr.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $jury_rapporteur1 ?? '…………………………………………………' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Rapporteur
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                Pr.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $jury_rapporteur2 ?? '…………………………………………………' }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Rapporteur
            </td>
        </tr>
    </table>

    {{-- NOTES --}}
    <p class="label">Note du Contenu <span class="label-note">*(En prenant en compte l'appréciation de l'entreprise)*</span></p>
    <p class="note">C &nbsp;<strong>=</strong>&nbsp; {{ $note_contenu ?? '…………………' }}</p>

    <p class="label">Note du Mémoire</p>
    <p class="note">M &nbsp;<strong>=&nbsp;&nbsp;</strong> {{ $note_memoire ?? '…………………' }}</p>

    <p class="label">Note de la Soutenance</p>
    <p class="note">S &nbsp;<strong>= </strong>&nbsp; {{ $note_soutenance ?? '…………………' }}</p>

    {{-- TABLEAU MOYENNE --}}
    <table class="table-moy">
        <tr><th>MOYENNE</th></tr>
        <tr>
            <td>
                <strong>Moyenne</strong>&nbsp;&nbsp;&nbsp;= C * 0,5 + M * 0,2 + S * 0,3 &nbsp;=&nbsp;
                {{ $moyenne ?? '…………………' }}
            </td>
        </tr>
    </table>

    {{-- DATE --}}
    <p class="date">Le :&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $date_soutenance ?? '……………………' }}</p>

    {{-- SIGNATURES --}}
    <p class="sig-title">Signature des membres du jury :</p>
    <table class="table-sig">
        <tr>
            <td>Pr.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $jury_president_court ?? '……………………' }}</td>
            <td>Pr.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $jury_rapporteur1_court ?? '………………………' }}</td>
            <td>Pr.&nbsp;&nbsp;&nbsp;&nbsp;{{ $jury_rapporteur2_court ?? '…………………………' }}</td>
        </tr>
    </table>

</div>
</body>
</html>