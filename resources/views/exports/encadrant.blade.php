<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Encadrants — Soutenances PFE </title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9px; color: #1a1a1a; }

        /* ── En-tête document ── */
        .doc-header { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
        .doc-logo { width: 50px; height: 50px; border: 2px solid #1a3a5c; border-radius: 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4px; text-align: center; }
        .doc-logo-text { font-size: 10px; font-weight: 700; color: #1a3a5c; line-height: 1.1; }
        .doc-logo-sub  { font-size: 7px; color: #c8a84b; letter-spacing: 0.5px; margin-top: 2px; }
        .doc-center { flex: 1; text-align: center; }
        .doc-univ   { font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
        .doc-school { font-size: 8px; font-style: italic; margin-bottom: 3px; }
        .doc-dept   { font-size: 9px; font-weight: 700; margin-bottom: 3px; }
        .doc-title  { font-size: 10px; font-weight: 700; }
        .doc-year   { font-size: 8px; margin-top: 2px; color: #555; }

        hr.sep { border: none; border-top: 1.5px solid #1a3a5c; margin: 6px 0 10px; }

        .meta { font-size: 8px; color: #555; margin-bottom: 12px; text-align: right; }

        /* ── Bloc encadrant ── */
        .enc-block { margin-bottom: 18px; page-break-inside: avoid; }

        .enc-title {
            background: linear-gradient(90deg, #1a3a5c 0%, #2a5080 100%);
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            padding: 5px 10px;
            border-radius: 3px 3px 0 0;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.04em;
        }

        .enc-title .enc-badge {
            background: rgba(200,168,75,.35);
            border: 1px solid #c8a84b;
            color: #f0d98a;
            font-size: 8px;
            font-weight: 600;
            padding: 1px 7px;
            border-radius: 10px;
            margin-left: auto;
            white-space: nowrap;
        }

        /* ── Table étudiants ── */
        table { width: 100%; border-collapse: collapse; border: 0.5px solid #c5cde0; border-top: none; border-radius: 0 0 3px 3px; overflow: hidden; }

        thead tr { background: #dce8f6; }
        thead th {
            padding: 4px 8px;
            font-size: 7.5px;
            font-weight: 700;
            color: #1a3a5c;
            text-align: left;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-bottom: 1px solid #c5cde0;
            border-right: 0.5px solid #dde5f0;
        }
        thead th:last-child { border-right: none; }

        tbody tr { border-bottom: 0.5px solid #dde5f0; }
        tbody tr:nth-child(even) { background: #f5f8fd; }
        tbody tr:last-child { border-bottom: none; }

        tbody td {
            padding: 4px 8px;
            font-size: 8.5px;
            vertical-align: middle;
            border-right: 0.5px solid #dde5f0;
            color: #1a1a1a;
        }
        tbody td:last-child { border-right: none; }

        .td-num { width: 22px; text-align: center; color: #6b7089; font-size: 8px; font-weight: 600; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 7.5px; font-weight: 600; }
        .badge-filiere { background: #eeedfe; color: #3c3489; }
        .badge-heure   { background: #fdf6e3; color: #7a5c0a; border: 0.5px solid #e8d58a; }
        .badge-salle   { background: #e8eef7; color: #1a3a5c; border: 0.5px solid #c5cde0; }

        .etu-binome { display: block; font-size: 7.5px; color: #854F0B; margin-top: 1px; }

        .td-jury { font-size: 8px; color: #3d3d5c; }
        .jury-sep { color: #aaa; margin: 0 2px; }
    </style>
</head>
<body>

<div class="doc-header">
    <div class="doc-logo">
        <div class="doc-logo-text">ENSA</div>
        <div class="doc-logo-sub">AL-HOCEIMA</div>
    </div>
    <div class="doc-center">
        <div class="doc-univ">UNIVERSITE ABDELMALEK ESSAADI</div>
        <div class="doc-school">École Nationale des Sciences Appliquées d'Al-Hoceima - Maroc</div>
        <div class="doc-dept">Département Mathématiques Informatique</div>
        <div class="doc-title">Affectation des Encadrants - Projets de Fin d'Étude (Première Session)</div>
        <div class="doc-year">Année Universitaire : {{ date('Y') - 1 }}/{{ date('Y') }}</div>
    </div>
</div>
<hr class="sep">

<div class="meta">Généré le : {{ now()->format('d/m/Y à H:i') }}</div>

@php
    /**
     * $soutenances est une Collection groupée par nom d'encadrant :
     * Collection<string, Collection<Soutenance>>
     * Chaque Soutenance a les relations : student, binome, juries.professor, encadrant
     */
    $encadrants = [
        'BADI Imad'                  => ['CHENTOUF ISMAIL', 'ELAMRI BADR-EDDINE', 'AFKIR MOHAMED', 'OUTMANI OSSAMA'],
        'BENGAG Amina'               => ['FETTAH Afaf', 'ELAMRI WIJDANE', 'ACHBOUQ HOUSNI'],
        'BOUJRAF Ahmed'              => ['ARBAHI Jawad', 'HAMMOUCHI SALMA', 'NIBANI GHITA'],
        'ADDAM Mohamed'              => ['LINABOUI Ali', 'RAMKADDAM ILyat', 'LFELLOUS RIM'],
        'BOUHAFER Fadwa'             => ['AMGHAR Nawal', 'CHBIROU Aymane', 'AKKOUH LOKMANE', 'NIGROU NOUHAILA'],
        'BOUDAA Tarik'               => ['AKKAOUI Hamza', 'EL HADDOUCHI IMANE', 'LARHRABLI KAWTAR'],
        'ALLAOUZI Imane'             => ['HABBA Salma', 'BOUGAYDANE Mohamed', 'BOURMICH CHAYMAE', 'ELMAHJOU ...'],
        'ELHADDADI Anass'            => ['AASROUN BOUBKAR', 'ANIK Youssef', 'FADIL WAHIBA', 'MARBOUH YOUNES'],
        'KHAMJANE Aziz'              => ['ALLOUCH HAFSSA', 'EL HADDADI Tarek', 'GUESSOUS IKRAM', 'EL BOUTAHERI NAJMA'],
        'BOUAZZA El Haj'             => ['NEMROUCH Ghizlane', 'BOUZKRAOUI IMAD', 'BOUAMOUD IMAD EDDINE'],
        'DADI El Wardani'            => ['HAMDAOUI Souhayla', 'TIKOUK JAMAL', 'EL MANSOURI SAMIHA'],
        'BAHRI Abdelkhalak'          => ['FAKRACH Jihad', 'ELMOURTAJI Salmane', 'MOUDNI HOUDA', 'CHAKIR FATIMA EZ-ZAHRA'],
        'MORADI Fouzia'              => ['ELOUAFI Fatima', 'FANNICH SALMA', 'ELGHARBAOUI ABDELGHAFOR'],
        'KANNOUF Nabil'              => ['SAIDY Nihal', 'BOUKHARI MOHAMED HAITHAM', 'MAGHOUTI AYMANE', 'EL MZOURI FATIMA-ZAHRA'],
        'ROUTAIB Hayat'              => ['TAOUIL Hafsa', 'EL HANAFI MOHAMMED AMINE', 'LEKRARI TAHA', 'EL MEFTAHI SOUHAYLA'],
        'RAGRAGUI Anouar'            => ['ABIDAR ABDESSAMAD', 'ROUCHDI Salaheddine', 'MOTASSIM HAMZA', 'BENLAMKADAM ZAKARIA'],
        'CHERRADI Mohamed'           => ['OUAHASSOUNE YOUSSEF', 'EL HAMCHI KENZA', 'EL BOUMASHOULI NAOUAR'],
        'MOUHIB Ibtihal'             => ['HAI OMAR', 'EL MORABIT KAMAR', 'BEN TOUHAMI MOHAMED RIDA'],
        'EL MAROUANI Mohamed'        => ['LAMINI Ouassima', 'AMMARI HIBA', 'KELLALI SALMA', 'EL AISSAOUY FATIMA ELZAHRAE'],
        'RAFII ZAKANI Fatima'        => ['NAJIB Mohamed', 'AMHIL INASS', 'BOUKAYOUA LOUBNA', 'SAOUABEDDINE YOUNES'],
        'LAHJOUJI ELIDRISSI Ahmed'   => ['AL AYACHI Rania', 'JARFI ACHRAF', 'BOUCHTA NOUHAYLA', 'HNIKA ...'],
        'ABOUELHANOUNE Younes'       => ['WANAIM NAIMA', 'AMEZIAN DOUHA', 'MOUNTASSIR CHADI'],
        'EL MORABIT Yasmina'         => ['NADI HANANE', 'BENABBOU OUSSAMA', 'LAGHMAM NADA'],
        'AMATTOUCH Mohamed Ridouan'  => ['ACHAHBAR OUSSAMA', 'BACHIRI JAWAD', 'MOUJIB OUMAIMA'],
        'SAOU Abdelmonaim'           => ['DAHRAOUI Achraf', 'LMEKKEDDEM OUSSAMA', 'NIDIKIN SOUMIA'],
        'OUALDCHAIB Sara'            => ['LAMINI Abdellah', 'HANINE KHALID', 'WANAIM ESSAADIA'],
        'BELLAALI Fatima'            => ['SBAI Salma', 'MERHRIOUI CHAYMAE'],
    ];
@endphp

@foreach($soutenances as $encadrantNom => $group)
@php
    $total = $group->count();
@endphp
<div class="enc-block">

    <div class="enc-title">
        Pr. {{ $encadrantNom }}
        <span class="enc-badge">{{ $total }} étudiant{{ $total > 1 ? 's' : '' }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="td-num">#</th>
                <th>Étudiant(s)</th>
                <th style="width:54px">Filière</th>
                <th style="width:56px">Date</th>
                <th style="width:46px">Créneau</th>
                <th style="width:38px">Salle</th>
                <th>Jury 1</th>
                <th>Jury 2</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group as $i => $s)
            @php
                $nom1    = ($s->student->prenom ?? '') . ' ' . ($s->student->nom ?? '');
                $nom2    = $s->binome
                               ? (($s->binome->prenom ?? '') . ' ' . ($s->binome->nom ?? ''))
                               : null;
                $filiere = $s->student->filiere ?? '';
                $debut   = \Carbon\Carbon::createFromFormat('H:i', $s->heure)->format('G') . 'h';
                $fin     = \Carbon\Carbon::createFromFormat('H:i', $s->heure)->addHour()->format('G') . 'h';
                $creneau = $debut . '–' . $fin;
                $jury    = $s->juries;
            @endphp
            <tr>
                <td class="td-num">{{ $i + 1 }}</td>
                <td>
                    {{ $nom1 }}
                    @if($nom2)<span class="etu-binome">+ {{ $nom2 }}</span>@endif
                </td>
                <td><span class="badge badge-filiere">{{ $filiere ?: '—' }}</span></td>
                <td>{{ \Carbon\Carbon::parse($s->date)->format('d/m/Y') }}</td>
                <td><span class="badge badge-heure">{{ $creneau }}</span></td>
                <td><span class="badge badge-salle">{{ $s->salle }}</span></td>
                <td class="td-jury">{{ $jury->get(0)?->professor->nom ?? '—' }}</td>
                <td class="td-jury">{{ $jury->get(1)?->professor->nom ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
@endforeach

</body>
</html>