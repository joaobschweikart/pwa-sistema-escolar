@props(['titulo' => null])
@php
    $u = auth()->user();
    $links = [
        ['rota' => 'painel', 'ativo' => 'painel', 'icone' => 'casa', 'texto' => 'Painel', 'inferior' => true],
        ['rota' => 'gestao.turmas.index', 'ativo' => 'gestao.turmas.*', 'icone' => 'turmas', 'texto' => 'Turmas', 'inferior' => true],
        ['rota' => 'gestao.alunos.index', 'ativo' => 'gestao.alunos.*', 'icone' => 'alunos', 'texto' => 'Alunos', 'inferior' => true],
        ['rota' => 'gestao.conflitos.index', 'ativo' => 'gestao.conflitos.*', 'icone' => 'alerta', 'texto' => 'Conflitos', 'inferior' => true],
        ['rota' => 'gestao.usuarios.index', 'ativo' => 'gestao.usuarios.*', 'icone' => 'usuarios', 'texto' => $u->ehAdmin() ? 'Usuários' : 'Professores'],
        ['rota' => 'gestao.auditoria.index', 'ativo' => 'gestao.auditoria.*', 'icone' => 'auditoria', 'texto' => 'Auditoria de notas'],
    ];
    if ($u->ehAdmin()) {
        array_splice($links, 1, 0, [['rota' => 'gestao.escolas.index', 'ativo' => 'gestao.escolas.*', 'icone' => 'escola', 'texto' => 'Escolas']]);
        $links[] = ['rota' => 'gestao.disciplinas.index', 'ativo' => 'gestao.disciplinas.*', 'icone' => 'livro', 'texto' => 'Disciplinas'];
    }
@endphp
<x-layouts.base :titulo="$titulo ?? null">
<div x-data="{ menu: false }" class="min-h-full">
    {{-- Barra lateral (tablet/desktop) --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white md:flex">
        <a href="{{ route('painel') }}" class="flex h-16 items-center gap-3 border-b border-slate-200 px-5">
            <img src="/icons/icon.svg" alt="" class="size-9">
            <span class="leading-tight"><span class="block font-bold text-marca-800">Diário DEM</span><span class="text-xs text-slate-500">Gestão escolar</span></span>
        </a>
        <nav class="flex-1 space-y-1 overflow-y-auto p-3" aria-label="Navegação principal">
            @foreach ($links as $l)
                <a href="{{ route($l['rota']) }}" @class(['link-nav', 'link-nav-ativo' => request()->routeIs($l['ativo'])])>
                    <x-icone :nome="$l['icone']" /> {{ $l['texto'] }}
                </a>
            @endforeach
            <a href="{{ url('/docs/api') }}" class="link-nav" target="_blank" rel="noopener"><x-icone nome="codigo" /> Documentação da API</a>
        </nav>
        <div class="border-t border-slate-200 p-3">
            <p class="truncate px-3 text-sm font-semibold">{{ $u->name }}</p>
            <p class="truncate px-3 text-xs text-slate-500">{{ $u->papel->rotulo() }}{{ $u->escola ? ' · '.$u->escola->nome : '' }}</p>
            <form method="POST" action="{{ route('logout') }}" data-logout class="mt-2">
                @csrf
                <button class="link-nav w-full"><x-icone nome="sair" /> Sair</button>
            </form>
        </div>
    </aside>

    {{-- Barra superior (celular) --}}
    <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur md:hidden">
        <a href="{{ route('painel') }}" class="flex items-center gap-2 font-bold text-marca-800"><img src="/icons/icon.svg" alt="" class="size-8"> Diário DEM</a>
        <span x-data x-show="!$store.conexao.online" x-cloak class="selo bg-amber-100 text-amber-900">Offline</span>
    </header>

    <div class="com-nav-inferior md:pl-64">
        <div x-data x-show="!$store.conexao.online" x-cloak class="hidden bg-amber-100 px-6 py-2 text-sm text-amber-900 md:block">
            Sem conexão. As telas de gestão precisam de internet.
        </div>
        <main class="mx-auto max-w-6xl px-4 py-5 sm:px-6 lg:px-8">
            <x-mensagens />
            {{ $slot }}
        </main>
    </div>

    {{-- Navegação inferior (celular) --}}
    <nav class="nav-inferior fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-slate-200 bg-white md:hidden" aria-label="Navegação inferior">
        @foreach (array_filter($links, fn ($l) => $l['inferior'] ?? false) as $l)
            <a href="{{ route($l['rota']) }}" @class(['flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] font-medium', 'text-marca-700' => request()->routeIs($l['ativo']), 'text-slate-600' => ! request()->routeIs($l['ativo'])])>
                <x-icone :nome="$l['icone']" class="size-6" /> {{ $l['texto'] }}
            </a>
        @endforeach
        <button type="button" @click="menu = true" class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] font-medium text-slate-600">
            <x-icone nome="menu" class="size-6" /> Mais
        </button>
    </nav>

    {{-- Menu "Mais" (celular) --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-40 md:hidden" role="dialog" aria-modal="true" aria-label="Mais opções">
        <div class="absolute inset-0 bg-slate-900/40" @click="menu = false"></div>
        <div class="nav-inferior absolute inset-x-0 bottom-0 rounded-t-2xl bg-white p-4 shadow-xl" x-transition>
            <div class="mb-3 flex items-center justify-between">
                <div><p class="font-semibold">{{ $u->name }}</p><p class="text-xs text-slate-500">{{ $u->papel->rotulo() }}</p></div>
                <button class="btn-fantasma px-3" @click="menu = false" aria-label="Fechar"><x-icone nome="x" /></button>
            </div>
            <nav class="space-y-1">
                @foreach (array_filter($links, fn ($l) => ! ($l['inferior'] ?? false)) as $l)
                    <a href="{{ route($l['rota']) }}" class="link-nav"><x-icone :nome="$l['icone']" /> {{ $l['texto'] }}</a>
                @endforeach
                <a href="{{ url('/docs/api') }}" class="link-nav"><x-icone nome="codigo" /> Documentação da API</a>
                <form method="POST" action="{{ route('logout') }}" data-logout>
                    @csrf
                    <button class="link-nav w-full text-red-700"><x-icone nome="sair" /> Sair</button>
                </form>
            </nav>
        </div>
    </div>
</div>
</x-layouts.base>
