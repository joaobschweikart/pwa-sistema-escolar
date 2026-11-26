<x-layouts.app :titulo="$turma->exists ? 'Editar turma' : 'Nova turma'">
    <x-cabecalho :titulo="$turma->exists ? 'Editar turma' : 'Nova turma'" />
    <form method="POST" action="{{ $turma->exists ? route('gestao.turmas.update', $turma) : route('gestao.turmas.store') }}" class="cartao max-w-2xl space-y-4 p-5">
        @csrf @if ($turma->exists) @method('PUT') @endif
        @if (auth()->user()->ehAdmin())
            <x-selecao nome="escola_id" rotulo="Escola" :valor="$turma->escola_id" :opcoes="$escolas->pluck('nome', 'id')" obrigatorio />
        @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo nome="nome" rotulo="Nome da turma" :valor="$turma->nome" placeholder="Ex.: 5º ano A" obrigatorio />
            <x-campo nome="serie" rotulo="Série/ano" :valor="$turma->serie" placeholder="Ex.: 5º ano" obrigatorio />
            <x-selecao nome="turno" rotulo="Turno" :valor="$turma->turno" :opcoes="\App\Infrastructure\Persistence\Models\Turma::TURNOS" obrigatorio />
            <x-campo nome="ano_letivo" rotulo="Ano letivo" tipo="number" :valor="$turma->ano_letivo" inputmode="numeric" obrigatorio />
        </div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ $turma->exists ? route('gestao.turmas.show', $turma) : route('gestao.turmas.index') }}" class="btn-secundario">Cancelar</a>
            <button class="btn-primario">Salvar</button>
        </div>
    </form>
</x-layouts.app>
