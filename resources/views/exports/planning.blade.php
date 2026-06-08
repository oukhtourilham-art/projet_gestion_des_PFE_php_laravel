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
        th { background-color: #1a3a5c; color: white; padding: 5px; font-size: 10px; }
        td { padding: 4px; border-bottom: 1px solid #ddd; font-size: 9px; }
        tr:nth-child(even) { background-color: #f5f5f5; }
    </style>
</head>
<body>

    <h1>Planning des Soutenances PFE — {{ date('Y') }}</h1>
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
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{ $s->student->encadrant->nom ?? '-' }}
                    {{ $s->student->encadrant->prenom ?? '' }}
                </td>
                <td>
                    @if($s->juries->count() > 0)
                        {{ $s->juries[0]->professor->nom ?? '-' }}
                        {{ $s->juries[0]->professor->prenom ?? '' }}
                    @else - @endif
                </td>
                <td>
                    @if($s->juries->count() > 1)
                        {{ $s->juries[1]->professor->nom ?? '-' }}
                        {{ $s->juries[1]->professor->prenom ?? '' }}
                    @else - @endif
                </td>
                <td>{{ $s->date_soutenance ?? '-' }}</td>
                <td>{{ $s->heure_debut ?? '-' }}</td>
                <td>{{ $s->salle ?? '-' }}</td>
                <td>
                    {{ $s->student->nom ?? '-' }}
                    @if($binome)<br>{{ $binome->nom }}@endif
                </td>
                <td>
                    {{ $s->student->prenom ?? '-' }}
                    @if($binome)<br>{{ $binome->prenom }}@endif
                </td>
                <td>
                    {{ $s->student->filiere ?? '-' }}
                    @if($binome)<br>{{ $binome->filiere }}@endif
                </td>
                </tr>
                @endforeach
        </tbody>
    </table>

</body>
</html>