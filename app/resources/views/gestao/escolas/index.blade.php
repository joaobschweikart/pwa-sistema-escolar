<x-layouts.app titulo="Escolas">
    <x-cabecalho titulo="Escolas" subtitulo="Unidades da rede municipal.">
        <x-slot:acoes><a href="{{ route('gestao.escolas.create') }}" class="btn-primario"><x-icone nome="mais" /> Nova escola</a></x-slot:acoes>
    </x-cabecalho>
    @if ($escolas->isEmpty())
        <x-vazio />
    @else
        <div class="cartao hidden overflow-hidden md:block">
            <table class="tabela">
                <thead><tr><th>Escola</th><th>INEP</th><th>Município</th><th class="text-right">Turmas</th><th class="text-right">Alunos</th><th></th></tr></thead>
                <tbody>
                @foreach ($escolas as $e)
                    <tr>
                        <td class="font-medium">{{ $e->nome }}<p class="text-xs text-slate-500">{{ $e->endereco }}</p></td>
                        <td>{{ $e->codigo_inep ?? '—' }}</td>
                        <td>{{ $e->municipio->nome }}/{{ $e->municipio->uf }}</td>
                        <td class="text-right">{{ $e->turmas_count }}</td>
                        <td class="text-right">{{ $e->alunos_count }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('gestao.escolas.edit', $e) }}" class="btn-fantasma px-3"><x-icone nome="lapis" /> Editar</a>
                            <x-excluir :acao="route('gestao.escolas.destroy', $e)" />
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <ul class="space-y-3 md:hidden">
            @foreach ($escolas as $e)
                <li class="cartao p-4">
                    <p class="font-semibold">{{ $e->nome }}</p>
                    <p class="text-sm text-slate-600">INEP {{ $e->codigo_inep ?? '—' }} · {{ $e->turmas_count }} turmas · {{ $e->alunos_count }} alunos</p>
                    <div class="mt-3 flex gap-2">
                        <a href="{{ route('gestao.escolas.edit', $e) }}" class="btn-secundario flex-1"><x-icone nome="lapis" /> Editar</a>
                        <x-excluir :acao="route('gestao.escolas.destroy', $e)" />
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $escolas->links() }}</div>
    @endif
</x-layouts.app>
