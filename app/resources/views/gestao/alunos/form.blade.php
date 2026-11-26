<x-layouts.app :titulo="$aluno->exists ? 'Editar aluno' : 'Novo aluno'">
    <x-cabecalho :titulo="$aluno->exists ? 'Editar aluno' : 'Novo aluno'" subtitulo="Dados pessoais de menores de idade: acesso restrito à escola (LGPD)." />
    <form method="POST" action="{{ $aluno->exists ? route('gestao.alunos.update', $aluno) : route('gestao.alunos.store') }}" class="cartao max-w-2xl space-y-4 p-5">
        @csrf @if ($aluno->exists) @method('PUT') @endif
        @if (auth()->user()->ehAdmin())
            <x-selecao nome="escola_id" rotulo="Escola" :valor="$aluno->escola_id" :opcoes="$escolas->pluck('nome', 'id')" obrigatorio />
        @endif
        <x-campo nome="nome" rotulo="Nome completo" :valor="$aluno->nome" obrigatorio />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo nome="codigo" rotulo="Código do aluno" :valor="$aluno->codigo" obrigatorio />
            <x-campo nome="data_nascimento" rotulo="Data de nascimento" tipo="date" :valor="$aluno->data_nascimento?->format('Y-m-d')" obrigatorio />
        </div>
        <x-campo nome="responsavel" rotulo="Responsável" :valor="$aluno->responsavel" />
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('gestao.alunos.index') }}" class="btn-secundario">Cancelar</a>
            <button class="btn-primario">Salvar</button>
        </div>
    </form>
    @if ($aluno->exists)
        <div class="mt-3 max-w-2xl text-right"><x-excluir :acao="route('gestao.alunos.destroy', $aluno)" confirmacao="Excluir este aluno?" /></div>
    @endif
</x-layouts.app>
