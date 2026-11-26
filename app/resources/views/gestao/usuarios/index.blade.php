@php $titulo = auth()->user()->ehAdmin() ? 'Usuários' : 'Professores'; @endphp
<x-layouts.app :titulo="$titulo">
    <x-cabecalho :titulo="$titulo" :subtitulo="auth()->user()->ehAdmin() ? 'Admins da Secretaria, gestores e professores.' : 'Professores da sua escola.'">
        <x-slot:acoes><a href="{{ route('gestao.usuarios.create') }}" class="btn-primario"><x-icone nome="mais" /> Novo</a></x-slot:acoes>
    </x-cabecalho>
    @if (auth()->user()->ehAdmin())
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (['' => 'Todos', 'admin' => 'Admins', 'gestor' => 'Gestores', 'professor' => 'Professores'] as $p => $r)
                <a href="{{ route('gestao.usuarios.index', array_filter(['papel' => $p])) }}" @class(['btn px-3', 'bg-marca-700 text-white' => request('papel', '') === $p, 'btn-secundario' => request('papel', '') !== $p])>{{ $r }}</a>
            @endforeach
        </div>
    @endif
    @if ($usuarios->isEmpty())
        <x-vazio />
    @else
        <div class="cartao hidden overflow-hidden md:block">
            <table class="tabela">
                <thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Escola</th><th>Situação</th><th></th></tr></thead>
                <tbody>
                @foreach ($usuarios as $usr)
                    <tr>
                        <td class="font-medium">{{ $usr->name }}</td>
                        <td>{{ $usr->email }}</td>
                        <td>{{ $usr->papel->rotulo() }}</td>
                        <td>{{ $usr->escola?->nome ?? 'Secretaria' }}</td>
                        <td>@if ($usr->ativo)<span class="selo bg-emerald-100 text-emerald-800">Ativo</span>@else<span class="selo bg-slate-200 text-slate-700">Inativo</span>@endif</td>
                        <td class="text-right whitespace-nowrap">
                            @can('update', $usr)<a href="{{ route('gestao.usuarios.edit', $usr) }}" class="btn-fantasma px-3"><x-icone nome="lapis" /> Editar</a>@endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <ul class="space-y-3 md:hidden">
            @foreach ($usuarios as $usr)
                <li class="cartao flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $usr->name }}</p>
                        <p class="truncate text-sm text-slate-600">{{ $usr->email }}</p>
                        <p class="text-xs text-slate-500">{{ $usr->papel->rotulo() }} · {{ $usr->escola?->nome ?? 'Secretaria' }}{{ $usr->ativo ? '' : ' · inativo' }}</p>
                    </div>
                    @can('update', $usr)<a href="{{ route('gestao.usuarios.edit', $usr) }}" class="btn-secundario px-3" aria-label="Editar {{ $usr->name }}"><x-icone nome="lapis" /></a>@endcan
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $usuarios->links() }}</div>
    @endif
</x-layouts.app>
