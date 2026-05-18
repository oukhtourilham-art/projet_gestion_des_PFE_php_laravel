<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Affectation des Encadrants PFE</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { font-size: 14px; margin: 4px 0; }
        .header p { font-size: 11px; margin: 2px 0; }
        hr { border: 1px solid black; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #1a3a5c; color: white; padding: 6px; text-align: left; font-size: 11px; }
        td { padding: 5px; border-bottom: 1px solid #ddd; font-size: 10px; vertical-align: top; }
        tr:nth-child(even) { background-color: #f5f5f5; }
        .no-students { color: #999; font-style: italic; }
    </style>
</head>
<body>

    <div class="header">
        <p>UNIVERSITE ABDELMALEK ESSAADI</p>
        <p>École Nationale des Sciences Appliquées d'Al-Hoceima</p>
        <h2>Département Mathématiques et Informatique</h2>
        <h2>Affectation des Encadrants — Projets de Fin d'Études</h2>
        <p>Année Universitaire : 2025-2026</p>
    </div>

    <hr>

    <table>
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:25%">Encadrant</th>
                <th style="width:15%">Discipline</th>
                <th style="width:5%">Nb</th>
                <th style="width:50%">Étudiants encadrés</th>
            </tr>
        </thead>
        <tbody>
            @foreach($professors as $i => $prof)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $prof->nom }} {{ $prof->prenom }}</td>
                <td>{{ $prof->discipline ?? '-' }}</td>
                <td>{{ $prof->students->count() }}</td>
                <td>
                    @if($prof->students->count() > 0)
                        @foreach($prof->students as $j => $student)
                            {{ $j + 1 }}. {{ $student->nom }} {{ $student->prenom }}
                            ({{ $student->filiere }})
                            @if(!$loop->last)<br>@endif
                        @endforeach
                    @else
                        <span class="no-students">Aucun étudiant assigné</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 10px; color: #666;">
        Généré le : {{ now()->format('d/m/Y à H:i') }} —
        Total : {{ $professors->sum(fn($p) => $p->students->count()) }} étudiants /
        {{ $professors->count() }} encadrants
    </p>

</body>
</html>