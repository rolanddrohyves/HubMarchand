<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - {{ $systemSettings->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="{{ $brandStyles }}" class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center p-4 sm:p-6">
        <div class="grid w-full max-w-5xl overflow-hidden rounded-3xl bg-white shadow-2xl shadow-slate-900/10 lg:grid-cols-2">
            <section class="relative hidden overflow-hidden bg-indigo-700 p-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-indigo-500/40"></div>
                <div class="absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-indigo-900/40"></div>

                <div class="relative">
                    <div class="mb-12 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-lg font-bold text-indigo-700">
                            HM
                        </div>
                        <span class="text-xl font-semibold tracking-tight">{{ $systemSettings->name }}</span>
                    </div>

                    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-200">Bienvenue</p>
                    <h1 class="max-w-md text-4xl font-bold leading-tight">
                        Gérez votre activité en toute simplicité.
                    </h1>
                    <p class="mt-6 max-w-md text-base leading-7 text-indigo-100">
                        Retrouvez vos commandes, vos clients et vos performances depuis un espace unique.
                    </p>
                </div>

                <p class="relative text-sm text-indigo-200">&copy; {{ date('Y') }} {{ $systemSettings->name }}</p>
            </section>

            <section class="p-6 sm:p-10 lg:p-14">
                <div class="mx-auto max-w-md">
                    <div class="mb-8 lg:hidden">
                        <div class="mb-6 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">
                                HM
                            </div>
                            <span class="text-xl font-semibold tracking-tight text-slate-900">{{ $systemSettings->name }}</span>
                        </div>
                    </div>

                    <div class="mb-8">
                        <p class="mb-2 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">Espace marchand</p>
                        <h2 class="text-3xl font-bold tracking-tight text-slate-900">Connexion</h2>
                        <p class="mt-2 text-sm text-slate-500">Connectez-vous pour accéder à votre tableau de bord.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                            <p class="font-semibold">Impossible de vous connecter.</p>
                            <ul class="mt-1 list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Adresse e-mail</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                autocomplete="email"
                                required
                                autofocus
                                class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10"
                                placeholder="vous@exemple.com"
                            >
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-700">Mot de passe</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                                class="block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10"
                                placeholder="••••••••"
                            >
                        </div>

                        <label class="flex items-center gap-3 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            Se souvenir de moi
                        </label>
                        <a href="{{ route('password.request') }}" class="block text-right text-sm font-semibold text-indigo-600">Mot de passe oublié ?</a>

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/30 active:scale-[.99]"
                        >
                            Se connecter
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
