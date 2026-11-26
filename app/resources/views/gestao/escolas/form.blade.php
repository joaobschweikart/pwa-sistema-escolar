<x-layouts.app :titulo="$escola->exists ? 'Editar escola' : 'Nova escola'">
    <x-cabecalho :titulo="$escola->exists ? 'Editar escola' : 'Nova escola'" />
    <form method="POST" action="{{ $escola->exists ? route('gestao.escolas.update', $escola) : route('gestao.escolas.store') }}" class="cartao max-w-2xl space-y-4 p-5">
        @csrf @if ($escola->exists) @method('PUT') @endif
        <x-selecao nome="municipio_id" rotulo="Município" :valor="$escola->municipio_id" :opcoes="$municipios->pluck('nome', 'id')" obrigatorio />
        <x-campo nome="nome" rotulo="Nome" :valor="$escola->nome" obrigatorio />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo nome="codigo_inep" rotulo="Código INEP" :valor="$escola->codigo_inep" inputmode="numeric" maxlength="8" />
            <x-campo nome="telefone" rotulo="Telefone" tipo="tel" :valor="$escola->telefone" />
        </div>
        <x-campo nome="endereco" rotulo="Endereço" :valor="$escola->endereco" />
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('gestao.escolas.index') }}" class="btn-secundario">Cancelar</a>
            <button class="btn-primario">Salvar</button>
        </div>
    </form>
</x-layouts.app>
