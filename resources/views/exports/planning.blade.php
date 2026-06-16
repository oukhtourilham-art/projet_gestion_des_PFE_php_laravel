<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning des Soutenances</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 15px; }
        h1 { font-size: 14px; text-align: center; }
        p { text-align: center; font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1a3a5c; color: white; padding: 5px; font-size: 10px; text-align: center; }
        td { padding: 4px; border: 1px solid #ccc; font-size: 9px; text-align: center; vertical-align: middle; }
    </style>
</head>
<body>

@php
    $palette = [
        ['#FFEB99','#7A5F00'], ['#C8E6C9','#1B5E20'], ['#BBDEFB','#0D3B6E'],
        ['#F8BBD0','#880E4F'], ['#D1C4E9','#4A148C'], ['#B2EBF2','#006064'],
        ['#FFE0B2','#E65100'], ['#DCEDC8','#33691E'], ['#E1BEE7','#6A1B9A'],
        ['#B3E5FC','#01579B'], ['#FFCCBC','#BF360C'], ['#FFF9C4','#8B6800'],
        ['#CFD8DC','#263238'], ['#F0F4C3','#5C6900'], ['#FCE4EC','#7B003A'],
        ['#E8F5E9','#1B4D1E'], ['#E3F2FD','#0A2E5C'], ['#FFF3E0','#7C3000'],
        ['#F3E5F5','#4A0072'], ['#E0F7FA','#004D52'],
    ];
    $profColors = [];
    $colorIndex = 0;

    foreach ($soutenances as $s) {
        $encKey = trim(($s->student->encadrant->nom ?? '') . ' ' . ($s->student->encadrant->prenom ?? ''));
        if ($encKey && !isset($profColors[$encKey])) {
            $profColors[$encKey] = $palette[$colorIndex % count($palette)];
            $colorIndex++;
        }
        if ($s->juries->count() > 0) {
            $j1Key = trim(($s->juries[0]->professor->nom ?? '') . ' ' . ($s->juries[0]->professor->prenom ?? ''));
            if ($j1Key && !isset($profColors[$j1Key])) {
                $profColors[$j1Key] = $palette[$colorIndex % count($palette)];
                $colorIndex++;
            }
        }
        if ($s->juries->count() > 1) {
            $j2Key = trim(($s->juries[1]->professor->nom ?? '') . ' ' . ($s->juries[1]->professor->prenom ?? ''));
            if ($j2Key && !isset($profColors[$j2Key])) {
                $profColors[$j2Key] = $palette[$colorIndex % count($palette)];
                $colorIndex++;
            }
        }
    }

    $filiereColors = [
        'GI'   => ['#BBDEFB', '#0D3B6E'],
        'DATA' => ['#C8E6C9', '#1B5E20'],
        'TDAI' => ['#F8BBD0', '#880E4F'],
    ];

    $dateColors = [];
    $datePalette = [
        ['#FFF9C4','#7A5F00'], ['#E1F5FE','#01579B'], ['#F3E5F5','#4A0072'],
        ['#E8F5E9','#1B4D1E'], ['#FCE4EC','#7B003A'], ['#FFF3E0','#7C3000'],
    ];
    $dateIndex = 0;
    foreach ($soutenances as $s) {
        $dateKey = \Carbon\Carbon::parse($s->date_soutenance)->format('d/m/Y');
        if (!isset($dateColors[$dateKey])) {
            $dateColors[$dateKey] = $datePalette[$dateIndex % count($datePalette)];
            $dateIndex++;
        }
    }
@endphp

    <h1>Planning des Soutenances des Projets de Fin d'Etude<br>
        (Première Session)<br>
        Année Universitaire {{ date('Y') - 1 }}/{{ date('Y') }}
    </h1>
    <p>Généré le : {{ now()->format('d/m/Y à H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Encadrant</th>
                <th>Membre jury 1</th>
                <th>Membre jury 2</th>
                <th>Date</th>
                <th>Heure</th>
                <th>Salle</th>
                <th>Nom étudiant</th>
                <th>Prénom étudiant</th>
                <th>Filière</th>
            </tr>
        </thead>
        <tbody>
            @foreach($soutenances as $i => $s)
            @php
                $binome = $s->binome_student_id
                    ? \App\Models\Student::find($s->binome_student_id)
                    : null;

                $encKey   = trim(($s->student->encadrant->nom ?? '') . ' ' . ($s->student->encadrant->prenom ?? ''));
                $encColor = $profColors[$encKey] ?? ['#eeeeee','#333333'];

                $j1Key   = $s->juries->count() > 0
                            ? trim(($s->juries[0]->professor->nom ?? '') . ' ' . ($s->juries[0]->professor->prenom ?? ''))
                            : '';
                $j1Color = $j1Key ? ($profColors[$j1Key] ?? ['#eeeeee','#333333']) : ['#eeeeee','#333333'];

                $j2Key   = $s->juries->count() > 1
                            ? trim(($s->juries[1]->professor->nom ?? '') . ' ' . ($s->juries[1]->professor->prenom ?? ''))
                            : '';
                $j2Color = $j2Key ? ($profColors[$j2Key] ?? ['#eeeeee','#333333']) : ['#eeeeee','#333333'];

                $dateStr   = \Carbon\Carbon::parse($s->date_soutenance)->format('d/m/Y');
                $dateColor = $dateColors[$dateStr] ?? ['#eeeeee','#333333'];

                $fil      = $s->student->filiere ?? '';
                $filColor = $filiereColors[$fil] ?? ['#eeeeee','#333333'];

                $binomeFil      = $binome->filiere ?? '';
                $binomeFilColor = $filiereColors[$binomeFil] ?? ['#eeeeee','#333333'];
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>

                {{-- Encadrant : couleur du prof --}}
                <td style="background-color: {{ $encColor[0] }}; color: {{ $encColor[1] }}; font-weight: 600;">
                    {{ $s->student->encadrant->nom ?? '-' }}
                    {{ $s->student->encadrant->prenom ?? '' }}
                </td>

                {{-- Jury 1 : couleur du prof --}}
                <td style="background-color: {{ $j1Color[0] }}; color: {{ $j1Color[1] }}; font-weight: 600;">
                    @if($s->juries->count() > 0)
                        {{ $s->juries[0]->professor->nom ?? '-' }}
                        {{ $s->juries[0]->professor->prenom ?? '' }}
                    @else - @endif
                </td>

                {{-- Jury 2 : couleur du prof --}}
                <td style="background-color: {{ $j2Color[0] }}; color: {{ $j2Color[1] }}; font-weight: 600;">
                    @if($s->juries->count() > 1)
                        {{ $s->juries[1]->professor->nom ?? '-' }}
                        {{ $s->juries[1]->professor->prenom ?? '' }}
                    @else - @endif
                </td>

                {{-- Date : couleur par date --}}
                <td style="background-color: {{ $dateColor[0] }}; color: {{ $dateColor[1] }}; font-weight: 700;">
                    {{ $dateStr }}
                </td>

                <td>{{ $s->heure_debut ?? '-' }}</td>
                <td>{{ $s->salle ?? '-' }}</td>

                {{-- Nom étudiant : couleur de la filière --}}
                <td style="background-color: {{ $filColor[0] }}; color: {{ $filColor[1] }};">
                    {{ $s->student->nom ?? '-' }}
                    @if($binome)<br>{{ $binome->nom }}@endif
                </td>

                {{-- Prénom étudiant : couleur de la filière --}}
                <td style="background-color: {{ $filColor[0] }}; color: {{ $filColor[1] }};">
                    {{ $s->student->prenom ?? '-' }}
                    @if($binome)<br>{{ $binome->prenom }}@endif
                </td>

                {{-- Filière : couleur de la filière --}}
                <td style="background-color: {{ $filColor[0] }}; color: {{ $filColor[1] }}; font-weight: 700;">
                    {{ $fil ?: '-' }}
                    @if($binome)<br>{{ $binomeFil }}@endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>