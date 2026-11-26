<x-layouts.guest titulo="Nova senha">
    <h1 class="mb-4 text-lg font-semibold">Criar nova senha</h1>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-campo nome="email" rotulo="E-mail" tipo="email" :valor="$email" autocomplete="username" obrigatorio />
        <x-campo nome="password" rotulo="Nova senha" tipo="password" autocomplete="new-password" ajuda="Mínimo de 8 caracteres, com letras e números." obrigatorio />
        <x-campo nome="password_confirmation" rotulo="Confirme a nova senha" tipo="password" autocomplete="new-password" obrigatorio />
        <button type="submit" class="btn-primario w-full">Salvar nova senha</button>
    </form>
</x-layouts.guest>
