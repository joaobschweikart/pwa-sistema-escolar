<x-layouts.app :titulo="$alvo->exists ? 'Editar usuário' : 'Novo usuário'">
    <x-cabecalho :titulo="$alvo->exists ? 'Editar usuário' : 'Novo usuário'" />
    <form method="POST" action="{{ $alvo->exists ? route('gestao.usuarios.update', $alvo) : route('gestao.usuarios.store') }}" class="cartao max-w-2xl space-y-4 p-5">
        @csrf @if ($alvo->exists) @method('PUT') @endif
        <x-campo nome="name" rotulo="Nome completo" :valor="$alvo->name" autocomplete="name" obrigatorio />
        <x-campo nome="email" rotulo="E-mail" tipo="email" :valor="$alvo->email" obrigatorio />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-selecao nome="papel" rotulo="Papel" :valor="$alvo->papel?->value" :opcoes="collect($papeis)->mapWithKeys(fn ($p) => [$p->value => $p->rotulo()])" obrigatorio />
            <x-selecao nome="escola_id" rotulo="Escola" :valor="$alvo->escola_id" :opcoes="$escolas->pluck('nome', 'id')" vazio="Secretaria (somente admin)" />
        </div>
        <x-campo nome="matricula_funcional" rotulo="Matrícula funcional" :valor="$alvo->matricula_funcional" />
        <x-campo nome="password" rotulo="{{ $alvo->exists ? 'Nova senha (deixe em branco para manter)' : 'Senha inicial' }}" tipo="password" autocomplete="new-password" ajuda="Mínimo de 8 caracteres, com letras e números." :obrigatorio="! $alvo->exists" />
        <label class="flex min-h-11 items-center gap-3 text-sm">
            <input type="hidden" name="ativo" value="0">
            <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $alvo->ativo ?? true)) class="size-5 rounded border-slate-300 text-marca-700">
            Usuário ativo (pode entrar no sistema)
        </label>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('gestao.usuarios.index') }}" class="btn-secundario">Cancelar</a>
            <button class="btn-primario">Salvar</button>
        </div>
    </form>
    @if ($alvo->exists)
        @can('delete', $alvo)<div class="mt-3 max-w-2xl text-right"><x-excluir :acao="route('gestao.usuarios.destroy', $alvo)" confirmacao="Excluir este usuário?" /></div>@endcan
    @endif
</x-layouts.app>
