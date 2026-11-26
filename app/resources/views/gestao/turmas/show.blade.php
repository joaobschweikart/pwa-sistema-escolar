<x-layouts.app :titulo="$turma->nome">
    <x-cabecalho :titulo="$turma->nome" :subtitulo="$turma->escola->nome.' · '.(\App\Infrastructure\Persistence\Models\Turma::TURNOS[$turma->turno] ?? $turma->turno).' · '.$turma->ano_letivo">
        <x-slot:acoes>
            <a href="{{ route('gestao.turmas.edit', $turma) }}" class="btn-secundario"><x-icone nome="lapis" /> Editar</a>
        </x-slot:acoes>
    </x-cabecalho>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Disciplinas e professores --}}
        <section class="lg:col-span-2">
            <h2 class="mb-3 text-base font-semibold">Disciplinas e professores</h2>
            <ul class="space-y-2">
                @forelse ($turma->alocacoes as $al)
                    <li class="cartao flex items-center justify-between gap-2 p-3">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $al->disciplina->nome }}</p>
                            <p class="truncate text-sm text-slate-600">{{ $al->professor->name }}</p>
                        </div>
                        <div class="flex shrink-0 items-center">
                            <a href="{{ route('gestao.diario.show', $al) }}" class="btn-fantasma px-3" title="Diário e boletim"><x-icone nome="grafico" /><span class="sr-only">Diário e boletim</span></a>
                            <form method="POST" action="{{ route('gestao.alocacoes.destroy', $al) }}" onsubmit="return confirm('Remover este vínculo?')">
                                @csrf @method('DELETE')
                                <button class="btn-fantasma px-3 text-red-700" title="Remover vínculo"><x-icone nome="x" /><span class="sr-only">Remover vínculo</span></button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li><x-vazio texto="Nenhuma disciplina vinculada." /></li>
                @endforelse
            </ul>
            @if ($disciplinasDisponiveis->isNotEmpty() && $professores->isNotEmpty())
                <form method="POST" action="{{ route('gestao.turmas.vincular', $turma) }}" class="cartao mt-3 space-y-3 p-4">
                    @csrf
                    <p class="text-sm font-semibold">Vincular professor</p>
                    <x-selecao nome="disciplina_id" rotulo="Disciplina" :opcoes="$disciplinasDisponiveis->pluck('nome', 'id')" obrigatorio />
                    <x-selecao nome="professor_id" rotulo="Professor" :opcoes="$professores->pluck('name', 'id')" obrigatorio />
                    <button class="btn-primario w-full">Vincular</button>
                </form>
            @endif
        </section>

        {{-- Matrículas --}}
        <section class="lg:col-span-3">
            <h2 class="mb-3 text-base font-semibold">Alunos matriculados ({{ $turma->matriculas->where('situacao', 'ativa')->count() }})</h2>
            @if ($alunosDisponiveis->isNotEmpty())
                <form method="POST" action="{{ route('gestao.turmas.matricular', $turma) }}" class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1"><x-selecao nome="aluno_id" rotulo="Matricular aluno da escola" :opcoes="$alunosDisponiveis->pluck('nome', 'id')" obrigatorio /></div>
                    <button class="btn-primario"><x-icone nome="mais" /> Matricular</button>
                </form>
            @endif
            <ul class="cartao divide-y divide-slate-100">
                @forelse ($turma->matriculas as $m)
                    <li class="flex items-center justify-between gap-3 px-4 py-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium"><span class="mr-2 text-slate-400">{{ str_pad((string) $m->numero_chamada, 2, '0', STR_PAD_LEFT) }}</span>{{ $m->aluno->nome }}</p>
                        </div>
                        <form method="POST" action="{{ route('gestao.matriculas.update', $m) }}" class="shrink-0">
                            @csrf @method('PATCH')
                            <label class="sr-only" for="sit-{{ $m->id }}">Situação</label>
                            <select id="sit-{{ $m->id }}" name="situacao" class="campo py-0 text-sm" onchange="this.form.submit()">
                                @foreach (\App\Infrastructure\Persistence\Models\Matricula::SITUACOES as $v => $r)
                                    <option value="{{ $v }}" @selected($m->situacao === $v)>{{ $r }}</option>
                                @endforeach
                            </select>
                        </form>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-sm text-slate-500">Nenhum aluno matriculado.</li>
                @endforelse
            </ul>
        </section>
    </div>

    <form method="POST" action="{{ route('gestao.turmas.destroy', $turma) }}" onsubmit="return confirm('Excluir esta turma?')" class="mt-8 text-right">
        @csrf @method('DELETE')
        <button class="btn-fantasma text-red-700"><x-icone nome="lixeira" /> Excluir turma</button>
    </form>
</x-layouts.app>
