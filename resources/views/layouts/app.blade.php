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
        <h1>Gestion des Soutenances PFE ENSAH</h1>

        <nav>
            <a href="{{ route('import.form') }}">Importer</a>
            <a href="{{ route('planning.index') }}">Planning</a>
            <a href="{{ route('export.index') }}">Exporter</a>
            <a href="{{ route('dashboard') }}">Dashboard</a>
        </nav>

    </header>

    <main>
        @if(session('success'))
            <div style="padding: 10px; background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; border-radius: 4px; margin-bottom: 20px;">
                ✅ {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div style="padding: 10px; background: #fffde7; color: #f57f17; border: 1px solid #fff59d; border-radius: 4px; margin-bottom: 20px;">
                ⚠️ {{ session('warning') }}
            </div>
        @endif

        @if(session('error'))
            <div style="padding: 10px; background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; border-radius: 4px; margin-bottom: 20px;">
                ❌ {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

</body>
</html>