<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Vérifier votre email · {{ $systemSettings->name }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
<main class="w-full max-w-md rounded-3xl bg-white p-8 text-center shadow-xl"><h1 class="text-2xl font-bold">Vérifiez votre adresse e-mail</h1><p class="mt-3 text-slate-600">Cliquez sur le lien reçu par email pour activer votre compte.</p>
@if(session('status'))<div class="mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('verification.send') }}" class="mt-6">@csrf<button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white">Renvoyer le lien</button></form>
<form method="POST" action="{{ route('logout.post') }}" class="mt-3">@csrf<button class="text-sm font-semibold text-slate-600">Se déconnecter</button></form></main></body></html>
