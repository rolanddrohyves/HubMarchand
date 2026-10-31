<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance · {{ $systemSettings->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="{{ $brandStyles }}" class="flex min-h-screen items-center justify-center bg-slate-100 px-4 text-slate-900">
    <main class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-xl">
        @if($systemSettings->logo_path)
            <img src="{{ asset('storage/'.$systemSettings->logo_path) }}" alt="{{ $systemSettings->name }}" class="mx-auto mb-6 h-20 w-20 rounded-2xl object-contain">
        @endif
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Maintenance en cours</p>
        <h1 class="mt-3 text-3xl font-bold">La plateforme sera bientôt disponible</h1>
        <p class="mt-4 text-slate-600">Nous effectuons actuellement une intervention technique. Veuillez réessayer dans quelques instants.</p>
        @if($systemSettings->support_email)
            <a href="mailto:{{ $systemSettings->support_email }}" class="mt-6 inline-block font-semibold text-cyan-700">{{ $systemSettings->support_email }}</a>
        @endif
    </main>
</body>
</html>
