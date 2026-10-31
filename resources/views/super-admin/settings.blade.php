<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Paramètres système · HubMarchand</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body style="{{ $brandStyles }}" class="min-h-screen bg-slate-100 text-slate-900 antialiased">@include('super-admin.partials.nav')
<main class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <header><p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Administration plateforme</p><h1 class="mt-2 text-3xl font-bold">Paramètres système</h1><p class="mt-2 text-slate-600">Identité, logo, couleurs et contacts de la plateforme.</p></header>
    @if(session('status'))<div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('super-admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">@csrf @method('PUT')
        <div><label class="mb-2 block text-sm font-semibold">Nom du système</label><input name="name" required value="{{ old('name', $settings->name) }}" class="w-full rounded-xl border border-slate-300 px-3 py-3"></div>
        <div><label class="mb-2 block text-sm font-semibold">Logo du système</label>@if($settings->logo_path)<img src="{{ asset('storage/'.$settings->logo_path) }}" class="mb-3 h-16 w-16 rounded-xl object-contain" alt="Logo système">@endif<input name="logo" type="file" accept=".jpg,.jpeg,.png,.webp,.svg" class="w-full rounded-xl border border-slate-300 px-3 py-3 text-sm"></div>
        <div class="grid gap-4 sm:grid-cols-3"><label class="text-sm font-semibold">Couleur principale<input name="primary_color" type="color" value="{{ old('primary_color', $settings->primary_color) }}" class="mt-2 h-12 w-full rounded-lg"></label><label class="text-sm font-semibold">Couleur secondaire<input name="secondary_color" type="color" value="{{ old('secondary_color', $settings->secondary_color) }}" class="mt-2 h-12 w-full rounded-lg"></label><label class="text-sm font-semibold">Couleur accent<input name="accent_color" type="color" value="{{ old('accent_color', $settings->accent_color) }}" class="mt-2 h-12 w-full rounded-lg"></label></div>
        <div class="grid gap-4 sm:grid-cols-2"><input name="support_email" type="email" value="{{ old('support_email', $settings->support_email) }}" placeholder="Email support" class="rounded-xl border border-slate-300 px-3 py-3"><input name="support_phone" value="{{ old('support_phone', $settings->support_phone) }}" placeholder="Téléphone support" class="rounded-xl border border-slate-300 px-3 py-3"></div>
        <label class="flex items-center gap-2 text-sm"><input name="maintenance_mode" type="checkbox" value="1" @checked($settings->maintenance_mode) class="rounded border-slate-300"> Activer le mode maintenance</label>
        <button class="w-full rounded-xl bg-cyan-600 px-4 py-3 font-semibold text-white">Enregistrer le paramétrage système</button>
    </form>
</main></body></html>
