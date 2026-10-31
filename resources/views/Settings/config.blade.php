<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres de la boutique - HubMarchand</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
@include('partials.module-navbar')
    <main class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between gap-4">
            <div>
                <a href="{{ route('accueil') }}" class="text-sm font-medium text-cyan-700 hover:text-cyan-600">
                    Retour aux modules
                </a>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Paramètres de la boutique</h1>
                <p class="mt-2 text-slate-600">Configurez les informations principales de votre boutique.</p>
            </div>

            <a href="{{ route('logout') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                Déconnexion
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <p class="font-semibold">Les paramètres n'ont pas été enregistrés.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="team-member-form" method="POST" action="{{ route('settings.team.store') }}">
            @csrf
        </form>

        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Informations générales</h2>
                <p class="mt-1 text-sm text-slate-500">Ces informations sont utilisées pour identifier votre boutique.</p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="logo" class="mb-2 block text-sm font-medium text-slate-700">Logo de la boutique</label>
                        <div class="flex flex-wrap items-center gap-4">
                            @if ($store->logo_path)
                                <img src="{{ asset('storage/'.$store->logo_path) }}" alt="Logo de {{ $store->name }}" class="h-16 w-16 rounded-xl border border-slate-200 object-cover">
                            @else
                                <div class="flex h-16 w-16 items-center justify-center rounded-xl border border-dashed border-slate-300 text-xs font-semibold text-slate-400">Logo</div>
                            @endif
                            <input id="logo" name="logo" type="file" accept=".jpg,.jpeg,.png,.webp" class="block w-full max-w-md rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700">
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Formats acceptés : JPG, PNG ou WEBP. Taille maximale : 2 Mo.</p>
                    </div>

                    <div>
                        <label for="shop_name" class="mb-2 block text-sm font-medium text-slate-700">Nom de la boutique</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $store->name) }}" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                    </div>

                    <div>
                        <label for="shop_email" class="mb-2 block text-sm font-medium text-slate-700">E-mail professionnel</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $store->email) }}" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                    </div>

                    <div class="md:col-span-2">
                        <label for="shop_description" class="mb-2 block text-sm font-medium text-slate-700">Description</label>
                        <textarea id="description" name="description" rows="4" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">{{ old('description', $store->description) }}</textarea>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Modes de paiement acceptés</h2>
                <p class="mt-1 text-sm text-slate-500">Choisissez les moyens proposés à la caisse.</p>
                @php($paymentMethods = old('payment_methods', $store->payment_methods ?: ['mobile_money', 'card', 'cash']))
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach (['mobile_money' => 'Mobile Money', 'card' => 'Carte bancaire', 'cash' => 'Espèces'] as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-cyan-300">
                            <input type="checkbox" name="payment_methods[]" value="{{ $value }}" @checked(in_array($value, $paymentMethods, true)) class="h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Marketplace de modules</h2>
                <p class="mt-1 text-sm text-slate-500">Activez uniquement les extensions utiles à votre équipe.</p>
                @php($enabledModules = old('enabled_modules', $store->enabled_modules ?? array_keys($modules)))
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ($modules as $key => $module)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-cyan-300">
                            <input type="checkbox" name="enabled_modules[]" value="{{ $key }}" @checked(in_array($key, $enabledModules, true)) class="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                            <span><span class="block font-semibold text-slate-800">{{ $module['name'] }}</span><span class="mt-1 block text-sm text-slate-500">{{ $module['description'] }}</span></span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Gestion de l’équipe</h2>
                <p class="mt-1 text-sm text-slate-500">Ajoutez des employés et attribuez leurs responsabilités.</p>
                <div class="mt-5 space-y-3">
                    @forelse ($members as $member)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                            <div><p class="font-semibold text-slate-800">{{ $member->name }}</p><p class="text-sm text-slate-500">{{ $member->email }} · {{ ['admin' => 'Administrateur', 'manager' => 'Gérant', 'seller' => 'Vendeur'][$member->role] ?? $member->role }}</p></div>
                            <button type="submit" form="remove-member-{{ $member->id }}" class="text-sm font-semibold text-red-600">Retirer</button>
                        </div>
                        <form id="remove-member-{{ $member->id }}" method="POST" action="{{ route('settings.team.destroy', $member) }}">@csrf @method('DELETE')</form>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Aucun employé ajouté.</p>
                    @endforelse
                </div>
                <div class="mt-6 grid gap-3 border-t border-slate-200 pt-5 sm:grid-cols-2">
                    <input name="name" form="team-member-form" required placeholder="Nom de l’employé" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <input name="email" form="team-member-form" type="email" required placeholder="Email professionnel" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <input name="password" form="team-member-form" type="password" required minlength="8" placeholder="Mot de passe temporaire" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <select name="role" form="team-member-form" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"><option value="admin">Administrateur</option><option value="manager">Gérant</option><option value="seller">Vendeur</option></select>
                    <button type="submit" form="team-member-form" class="rounded-xl bg-slate-800 px-4 py-3 text-sm font-semibold text-white sm:col-span-2">Ajouter à l’équipe</button>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Coordonnées</h2>
                <p class="mt-1 text-sm text-slate-500">Ajoutez les coordonnées visibles sur vos documents commerciaux.</p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">Téléphone</label>
                        <input id="phone" name="phone" type="tel" value="{{ old('phone', $store->phone) }}" placeholder="+33 1 00 00 00 00" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                    </div>

                    <div>
                        <label for="website" class="mb-2 block text-sm font-medium text-slate-700">Site internet</label>
                        <input id="website" name="website" type="url" value="{{ old('website', $store->website) }}" placeholder="https://exemple.com" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="mb-2 block text-sm font-medium text-slate-700">Adresse</label>
                        <input id="address" name="address" type="text" value="{{ old('address', $store->address) }}" placeholder="Adresse de la boutique" class="block w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-slate-900">Préférences régionales</h2>
                <p class="mt-1 text-sm text-slate-500">Ces préférences servent à afficher les prix et les dates correctement.</p>

                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="country" class="mb-2 block text-sm font-medium text-slate-700">Pays</label>
                        <select id="country" name="country" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                            @foreach (['BJ' => 'Bénin', 'BF' => 'Burkina Faso', 'CI' => 'Côte d’Ivoire', 'GW' => 'Guinée-Bissau', 'ML' => 'Mali', 'NE' => 'Niger', 'SN' => 'Sénégal', 'TG' => 'Togo'] as $code => $label)
                                <option value="{{ $code }}" @selected(old('country', $store->country) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="currency" class="mb-2 block text-sm font-medium text-slate-700">Devise</label>
                        <select id="currency" name="currency" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                            @foreach (['XOF' => 'Franc CFA BCEAO (F CFA)'] as $code => $label)
                                <option value="{{ $code }}" @selected(old('currency', $store->currency) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="timezone" class="mb-2 block text-sm font-medium text-slate-700">Fuseau horaire</label>
                        <select id="timezone" name="timezone" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                            @foreach (['Africa/Abidjan' => 'Abidjan', 'Africa/Bamako' => 'Bamako', 'Africa/Cotonou' => 'Cotonou', 'Africa/Dakar' => 'Dakar', 'Africa/Lome' => 'Lomé', 'Africa/Niamey' => 'Niamey', 'Africa/Ouagadougou' => 'Ouagadougou'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('timezone', $store->timezone) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="date_format" class="mb-2 block text-sm font-medium text-slate-700">Format des dates</label>
                        <select id="date_format" name="date_format" class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10">
                            @foreach (['d/m/Y' => '31/12/2026', 'm/d/Y' => '12/31/2026', 'Y-m-d' => '2026-12-31'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('date_format', $store->date_format) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-cyan-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyan-500">
                    Enregistrer les paramètres
                </button>
            </form>
        </div>
    </main>
</body>
</html>