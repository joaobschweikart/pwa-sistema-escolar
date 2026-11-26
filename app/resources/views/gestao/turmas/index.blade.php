<x-layouts.app titulo="Turmas">
    <x-cabecalho titulo="Turmas" subtitulo="Turmas, matrículas e professores vinculados por disciplina.">
        <x-slot:acoes><a href="{{ route('gestao.turmas.create') }}" class="btn-primario"><x-icone nome="mais" /> Nova turma</a></x-slot:acoes>
    </x-cabecalho>
    @if ($turmas->isEmpty())
        <x-vazio />
    @else
        <div class="cartao hidden overflow-hidden md:block">
            <table class="tabela">
                <thead><tr><th>Turma</th><th>Escola</th><th>Turno</th><th>Ano</th><th class="text-right">Alunos</th><th class="text-right">Disciplinas</th><th></th></tr></thead>
                <tbody>
                @foreach ($turmas as $t)
                    <tr>
                        <td class="font-medium">{{ $t->nome }}</td>
                        <td>{{ $t->escola->nome }}</td>
                        <td>{{ \App\Infrastructure\Persistence\Models\Turma::TURNOS[$t->turno] ?? $t->turno }}</td>
                        <td>{{ $t->ano_letivo }}</td>
                        <td class="text-right">{{ $t->matriculas_count }}</td>
                        <td class="text-right">{{ $t->alocacoes_count }}</td>
                        <td class="text-right"><a href="{{ route('gestao.turmas.show', $t) }}" class="btn-fantasma px-3">Abrir</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <ul class="space-y-3 md:hidden">
            @foreach ($turmas as $t)
                <li>
                    <a href="{{ route('gestao.turmas.show', $t) }}" class="cartao block p-4 hover:border-marca-600">
                        <p class="font-semibold">{{ $t->nome }} <span class="font-normal text-slate-500">· {{ $t->ano_letivo }}</span></p>
                        <p class="text-sm text-slate-600">{{ $t->escola->nome }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $t->matriculas_count }} alunos · {{ $t->alocacoes_count }} disciplinas · {{ \App\Infrastructure\Persistence\Models\Turma::TURNOS[$t->turno] ?? $t->turno }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $turmas->links() }}</div>
    @endif
</x-layouts.app>
