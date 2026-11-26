@props(['titulo', 'subtitulo' => null])
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ $titulo }}</h1>
        @if ($subtitulo)<p class="mt-1 text-sm text-slate-600">{{ $subtitulo }}</p>@endif
    </div>
    @isset($acoes)<div class="flex flex-wrap gap-2">{{ $acoes }}</div>@endisset
</div>
