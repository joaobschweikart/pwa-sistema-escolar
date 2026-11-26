<x-layouts.app titulo="Painel">
    <x-cabecalho :titulo="'Olá, '.str(auth()->user()->name)->before(' ')" :subtitulo="auth()->user()->ehAdmin() ? 'Visão da rede municipal' : 'Visão da escola '.auth()->user()->escola?->nome" />

    @if ($conflitosPendentes > 0)
        <a href="{{ route('gestao.conflitos.index') }}" class="mb-5 flex min-h-11 items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-amber-900 hover:bg-amber-100">
            <x-icone nome="alerta" class="size-6 shrink-0" />
            <span class="text-sm"><strong>{{ $conflitosPendentes }} conflito(s) de nota</strong> aguardando sua decisão.</span>
        </a>
    @endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($indicadores as $i)
            <a href="{{ $i['rota'] ?? '#' }}" class="cartao p-4 hover:border-marca-600">
                <p class="text-sm text-slate-600">{{ $i['rotulo'] }}</p>
                <p class="mt-1 text-3xl font-bold text-slate-900">{{ $i['valor'] }}</p>
            </a>
        @endforeach
    </div>

    <section class="mt-8">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-base font-semibold">Últimas alterações de nota</h2>
            <a href="{{ route('gestao.auditoria.index') }}" class="text-sm font-medium text-marca-700 hover:underline">Ver auditoria</a>
        </div>
        @include('gestao.auditoria._lista', ['registros' => $auditoria])
    </section>
</x-layouts.app>
