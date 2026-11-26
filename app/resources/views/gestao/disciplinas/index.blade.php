<x-layouts.app titulo="Disciplinas">
    <x-cabecalho titulo="Disciplinas" subtitulo="Componentes curriculares da rede municipal.">
        <x-slot:acoes><a href="{{ route('gestao.disciplinas.create') }}" class="btn-primario"><x-icone nome="mais" /> Nova disciplina</a></x-slot:acoes>
    </x-cabecalho>
    @if ($disciplinas->isEmpty())
        <x-vazio />
    @else
        <div class="cartao hidden overflow-hidden md:block">
            <table class="tabela">
                <thead><tr><th>Código</th><th>Disciplina</th><th class="text-right">Carga horária</th><th class="text-right">Turmas</th><th></th></tr></thead>
                <tbody>
                @foreach ($disciplinas as $d)
                    <tr>
                        <td class="font-mono">{{ $d->codigo }}</td>
                        <td class="font-medium">{{ $d->nome }}</td>
                        <td class="text-right">{{ $d->carga_horaria ? $d->carga_horaria.' h' : '—' }}</td>
                        <td class="text-right">{{ $d->alocacoes_count }}</td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('gestao.disciplinas.edit', $d) }}" class="btn-fantasma px-3"><x-icone nome="lapis" /> Editar</a>
                            <x-excluir :acao="route('gestao.disciplinas.destroy', $d)" />
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <ul class="space-y-3 md:hidden">
            @foreach ($disciplinas as $d)
                <li class="cartao flex items-center justify-between gap-3 p-4">
                    <div><p class="font-semibold">{{ $d->nome }}</p><p class="text-sm text-slate-600">{{ $d->codigo }} · {{ $d->alocacoes_count }} turmas</p></div>
                    <a href="{{ route('gestao.disciplinas.edit', $d) }}" class="btn-secundario px-3" aria-label="Editar {{ $d->nome }}"><x-icone nome="lapis" /></a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.app>
