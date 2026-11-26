<x-layouts.app :titulo="'Diário · '.$alocacao->turma->nome">
    <x-cabecalho :titulo="$alocacao->disciplina->nome.' · '.$alocacao->turma->nome" :subtitulo="'Professor(a): '.$alocacao->professor->name.' · '.$alocacao->turma->escola->nome">
        <x-slot:acoes><a href="{{ route('gestao.turmas.show', $alocacao->turma) }}" class="btn-secundario"><x-icone nome="voltar" /> Turma</a></x-slot:acoes>
    </x-cabecalho>

    <section>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-base font-semibold">Boletim</h2>
            <form method="GET" class="flex items-center gap-2">
                <label for="periodo" class="text-sm text-slate-600">Período</label>
                <select id="periodo" name="periodo" class="campo w-auto" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach ([1, 2, 3, 4] as $p)<option value="{{ $p }}" @selected($periodo === $p)>{{ $p }}º bimestre</option>@endforeach
                </select>
            </form>
        </div>
        @php $cor = fn ($s) => match ($s) { 'aprovado' => 'bg-emerald-100 text-emerald-800', 'recuperacao' => 'bg-amber-100 text-amber-900', 'reprovado_frequencia' => 'bg-red-100 text-red-800', default => 'bg-slate-100 text-slate-700' }; @endphp
        <div class="cartao hidden overflow-hidden md:block">
            <table class="tabela">
                <thead><tr><th>Nº</th><th>Aluno</th><th class="text-right">Média</th><th class="text-right">Frequência</th><th class="text-right">Faltas</th><th>Situação</th></tr></thead>
                <tbody>
                @foreach ($boletim as $b)
                    <tr>
                        <td class="text-slate-500">{{ $b['numero_chamada'] }}</td>
                        <td class="font-medium">{{ $b['aluno'] }}</td>
                        <td class="text-right font-mono">{{ $b['media'] !== null ? number_format($b['media'], 1, ',', '') : '—' }}</td>
                        <td class="text-right font-mono">{{ $b['frequencia'] !== null ? number_format($b['frequencia'], 1, ',', '').'%' : '—' }}</td>
                        <td class="text-right">{{ $b['faltas'] }}</td>
                        <td><span class="selo {{ $cor($b['situacao']) }}">{{ $b['situacao_rotulo'] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <ul class="space-y-2 md:hidden">
            @foreach ($boletim as $b)
                <li class="cartao flex items-center justify-between gap-3 p-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $b['aluno'] }}</p>
                        <p class="text-xs text-slate-500">Freq. {{ $b['frequencia'] !== null ? number_format($b['frequencia'], 1, ',', '').'%' : '—' }} · {{ $b['faltas'] }} falta(s)</p>
                        <span class="selo mt-1 {{ $cor($b['situacao']) }}">{{ $b['situacao_rotulo'] }}</span>
                    </div>
                    <p class="text-2xl font-bold">{{ $b['media'] !== null ? number_format($b['media'], 1, ',', '') : '—' }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section>
            <h2 class="mb-3 text-base font-semibold">Aulas registradas ({{ $aulas->count() }})</h2>
            <ul class="cartao divide-y divide-slate-100">
                @forelse ($aulas as $a)
                    <li class="px-4 py-3">
                        <p class="text-sm font-medium">{{ $a->data->format('d/m/Y') }} — {{ $a->conteudo }}</p>
                        <p class="text-xs text-slate-500">{{ $a->presentes_count }}/{{ $a->frequencias_count }} presentes · versão {{ $a->versao }}</p>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-slate-500">Nenhuma aula registrada.</li>
                @endforelse
            </ul>
        </section>
        <section>
            <h2 class="mb-3 text-base font-semibold">Avaliações</h2>
            <ul class="cartao divide-y divide-slate-100">
                @forelse ($avaliacoes as $av)
                    <li class="flex items-center justify-between px-4 py-3 text-sm">
                        <span><span class="font-medium">{{ $av->titulo }}</span> <span class="text-slate-500">· {{ $av->data->format('d/m') }} · peso {{ rtrim(rtrim(number_format($av->peso, 2, ',', ''), '0'), ',') }}</span></span>
                        <span class="text-slate-600">{{ $av->notas_count }} nota(s)</span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-slate-500">Nenhuma avaliação.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
