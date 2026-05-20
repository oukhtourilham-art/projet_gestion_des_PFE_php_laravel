<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Gestion Soutenances')</title>
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</head>
<body>

    <header>

        <img src="{{ asset('images/LogoEnsah.png') }}" alt="LogoEnsah">
        <h1>Gestion des Soutenances</h1>

        <nav>
            <a href="{{ route('import.form') }}">Importer</a>
            <a href="{{ route('planning.index') }}">Planning</a>
            <a href="{{ route('export.index') }}">Exporter</a>
            <a href="{{ route('dashboard') }}">Dashboard</a>
        </nav>

    </header>

    <main>
        @yield('content')
    </main>

</body>
</html>