<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Notifications · HubMarchand</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    @include('partials.module-navbar')
    <main class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <header><p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Centre de notifications</p><h1 class="mt-2 text-3xl font-bold">Vos notifications</h1></header>
        @if (session('status'))<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ session('status') }}</div>@endif
        <section class="space-y-3">
            @forelse($notifications as $notification)
                <article class="rounded-2xl border {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-amber-300 bg-amber-50' }} p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div><h2 class="font-bold">{{ $notification->data['title'] ?? 'Notification' }}</h2><p class="mt-2 text-sm text-slate-700">{{ $notification->data['message'] ?? '' }}</p><p class="mt-2 text-xs text-slate-500">{{ $notification->created_at->format('d/m/Y à H:i') }}</p></div>
                        @if (!$notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="text-xs font-semibold text-cyan-700">Marquer comme lue</button></form>@endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">Aucune notification.</div>
            @endforelse
        </section>
        {{ $notifications->links() }}
    </main>
</body>
</html>
