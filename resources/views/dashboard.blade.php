<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard PFE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body         { background: #f4f6f9; }
        .stat-card   { background: white; border-radius: 12px; padding: 20px;
                       text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,.07); }
        .stat-number { font-size: 2rem; font-weight: 600; }
        .card        { border-radius: 12px; border: none;
                       box-shadow: 0 2px 8px rgba(0,0,0,.07); }
        thead th     { background: #1e293b; color: white; font-weight: 500; }
    </style>
</head>
<body>
<div class="container py-4">

    <h2 class="fw-bold mb-1">Tableau de bord — Gestion des PFEs</h2>
    <p class="text-muted mb-4">Département Informatique </p>

    {{-- CARTES --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number" style="color:#4e73df">{{ $stats['etudiants'] }}</div>
                <div class="text-muted">Total étudiants</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number" style="color:#1cc88a">{{ $stats['profs'] }}</div>
                <div class="text-muted">Total professeurs</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number" style="color:#36b9cc">{{ $stats['soutenances'] }}</div>
                <div class="text-muted">Soutenances planifiées</div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- TABLEAU COMBINÉ --}}
        <div class="col-md-7">
            <div class="card p-3">
                <h5 class="mb-1">Participation par professeur</h5>
                <p class="text-muted small mb-3">Étudiants encadrés · soutenances assistés comme membre de jury</p>
                <table class="table table-sm table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Professeur</th>
                            <th class="text-center">Nb encadrés</th>
                            <th class="text-center">Nb jurys</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tableauCombine as $row)
                        <tr>
                            <td>{{ $row->nom_complet }}</td>
                            <td class="text-center">{{ $row->nb_encadres }}</td>
                            <td class="text-center">{{ $row->nb_jurys }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- DIAGRAMME CERCLE --}}
        <div class="col-md-5">
            <div class="card p-3">
                <h5 class="mb-1">Soutenances par filière</h5>
                <p class="text-muted small mb-3">3 filières du département</p>
                <div style="max-width:260px; margin:0 auto;">
                    <canvas id="chartFiliere"></canvas>
                </div>
                <table class="table table-sm mt-3">
                    <thead>
                        <tr>
                            <th>Filière</th>
                            <th class="text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($soutenancesParFiliere as $row)
                        <tr>
                            <td>{{ $row->filiere }}</td>
                            <td class="text-center fw-semibold">{{ $row->total }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    const labels = [];
    const data   = [];

    @foreach($soutenancesParFiliere as $row)
        labels.push("{{ $row->filiere }}");
        data.push({{ $row->total }});
    @endforeach

    new Chart(document.getElementById('chartFiliere'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: ['#378ADD', '#1D9E75', '#7F77DD'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            cutout: '55%',
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(c) {
                            const total = data.reduce((a, b) => a + b, 0);
                            const pct   = Math.round(c.parsed / total * 100);
                            return ' ' + c.parsed + ' étudiants (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>