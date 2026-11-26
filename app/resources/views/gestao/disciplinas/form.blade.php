<x-layouts.app :titulo="$disciplina->exists ? 'Editar disciplina' : 'Nova disciplina'">
    <x-cabecalho :titulo="$disciplina->exists ? 'Editar disciplina' : 'Nova disciplina'" />
    <form method="POST" action="{{ $disciplina->exists ? route('gestao.disciplinas.update', $disciplina) : route('gestao.disciplinas.store') }}" class="cartao max-w-xl space-y-4 p-5">
        @csrf @if ($disciplina->exists) @method('PUT') @endif
        <x-campo nome="nome" rotulo="Nome" :valor="$disciplina->nome" obrigatorio />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo nome="codigo" rotulo="Código" :valor="$disciplina->codigo" obrigatorio />
            <x-campo nome="carga_horaria" rotulo="Carga horária anual (h)" tipo="number" :valor="$disciplina->carga_horaria" inputmode="numeric" />
        </div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('gestao.disciplinas.index') }}" class="btn-secundario">Cancelar</a>
            <button class="btn-primario">Salvar</button>
        </div>
    </form>
</x-layouts.app>
