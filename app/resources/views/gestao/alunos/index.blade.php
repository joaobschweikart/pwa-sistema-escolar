<x-layouts.app titulo="Alunos">
    <x-cabecalho titulo="Alunos" subtitulo="Cadastro de alunos e turma atual.">
        <x-slot:acoes><a href="{{ route('gestao.alunos.create') }}" class="btn-primario"><x-icone nome="mais" /> Novo aluno</a></x-slot:acoes>
    </x-cabecalho>
    <form method="GET" class="mb-4 flex gap-2">
        <label for="busca" class="sr-only">Buscar por nome</label>
        <input id="busca" name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome…" class="campo" type="search">
        <button class="btn-secundario">Buscar</button>
    </form>
    @if ($alunos->isEmpty())
        <x-vazio />
    @else
        <div class="cartao hidden overflow-hidden md:block">
            <table class="tabela">
                <thead><tr><th>Aluno</th><th>Código</th><th>Nascimento</th><th>Turma</th>@if (auth()->user()->ehAdmin())<th>Escola</th>@endif<th></th></tr></thead>
                <tbody>
                @foreach ($alunos as $a)
                    <tr>
                        <td class="font-medium">{{ $a->nome }}</td>
                        <td class="font-mono text-slate-600">{{ $a->codigo }}</td>
                        <td>{{ $a->data_nascimento->format('d/m/Y') }}</td>
                        <td>{{ $a->matriculas->first()?->turma->nome ?? '—' }}</td>
                        @if (auth()->user()->ehAdmin())<td>{{ $a->escola->nome }}</td>@endif
                        <td class="text-right whitespace-nowrap"><a href="{{ route('gestao.alunos.edit', $a) }}" class="btn-fantasma px-3"><x-icone nome="lapis" /> Editar</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <ul class="space-y-3 md:hidden">
            @foreach ($alunos as $a)
                <li class="cartao flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $a->nome }}</p>
                        <p class="text-sm text-slate-600">{{ $a->matriculas->first()?->turma->nome ?? 'Sem turma' }} · {{ $a->data_nascimento->format('d/m/Y') }}</p>
                    </div>
                    <a href="{{ route('gestao.alunos.edit', $a) }}" class="btn-secundario px-3" aria-label="Editar {{ $a->nome }}"><x-icone nome="lapis" /></a>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $alunos->links() }}</div>
    @endif
</x-layouts.app>
