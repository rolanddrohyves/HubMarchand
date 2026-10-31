<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Mot de passe oublié · {{ $systemSettings->name }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
<main class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl"><h1 class="text-2xl font-bold">{{ $systemSettings->name }}</h1><p class="mt-2 text-sm text-slate-600">Recevez un lien pour réinitialiser votre mot de passe.</p>
@if(session('status'))<div class="mt-4 rounded-xl bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>@endif
@error('email')<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>@enderror
<form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">@csrf<input name="email" type="email" required value="{{ old('email') }}" placeholder="Adresse e-mail" class="w-full rounded-xl border border-slate-300 px-4 py-3"><button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white">Envoyer le lien</button></form>
<a href="{{ route('login') }}" class="mt-5 block text-center text-sm font-semibold text-indigo-600">Retour à la connexion</a></main></body></html>
