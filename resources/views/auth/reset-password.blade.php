<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Nouveau mot de passe · {{ $systemSettings->name }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4">
<main class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl"><h1 class="text-2xl font-bold">Nouveau mot de passe</h1>
@if($errors->any())<div class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">@csrf<input type="hidden" name="token" value="{{ $token }}"><input name="email" type="email" required value="{{ old('email', $email) }}" placeholder="Adresse e-mail" class="w-full rounded-xl border border-slate-300 px-4 py-3"><input name="password" type="password" required minlength="8" placeholder="Nouveau mot de passe" class="w-full rounded-xl border border-slate-300 px-4 py-3"><input name="password_confirmation" type="password" required minlength="8" placeholder="Confirmer le mot de passe" class="w-full rounded-xl border border-slate-300 px-4 py-3"><button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-semibold text-white">Réinitialiser</button></form></main></body></html>
