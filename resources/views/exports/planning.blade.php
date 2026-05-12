<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Planning des Soutenances</title>
</head>
<body>

    <h1>Planning des Soutenances — {{ date('Y') }}</h1>
    <p>Généré le : {{ now()->format('d/m/Y à H:i') }}</p>

    <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Étudiant</th>
                <th>Sujet</th>
                <th>Date</th>
                <th>Heure</th>
                <th>Salle</th>
                <th>Jury</th>
            </tr>
        </thead>
        <tbody>
            @foreach($soutenances as $i => $s)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $s->student->prenom }} {{ $s->student->nom }}</td>
                <td>{{ $s->sujet }}</td>
                <td>{{ $s->date }}</td>
                <td>{{ $s->heure }}</td>
                <td>{{ $s->salle }}</td>
                <td>
                    @foreach($s->juries as $j)
                        {{ $j->professor->nom }}<br>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>