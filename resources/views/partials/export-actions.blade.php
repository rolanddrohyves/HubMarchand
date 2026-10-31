@props(['resource'])
<div class="flex flex-wrap items-center gap-2" aria-label="Exporter les données">
    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Exporter</span>
    @foreach (['pdf' => 'PDF', 'xlsx' => 'Excel', 'docx' => 'Word'] as $format => $label)
        <a href="{{ route('exports.download', [$resource, $format]) }}" title="Exporter en {{ $label }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-600 transition hover:border-cyan-300 hover:text-cyan-700">
            {{ $label }}
        </a>
    @endforeach
</div>
