<x-layouts.guest titulo="Entrar">
    <h1 class="mb-4 text-lg font-semibold">Entrar</h1>
    <x-mensagens />
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-campo nome="email" rotulo="E-mail" tipo="email" autocomplete="username" inputmode="email" autofocus obrigatorio />
        <x-campo nome="password" rotulo="Senha" tipo="password" autocomplete="current-password" obrigatorio />
        <label class="flex min-h-11 items-center gap-3 text-sm text-slate-700">
            <input type="checkbox" name="lembrar" value="1" checked class="size-5 rounded border-slate-300 text-marca-700">
            Manter conectado neste dispositivo
        </label>
        <button type="submit" class="btn-primario w-full">Entrar</button>
    </form>
    <p class="mt-4 text-center text-sm text-slate-600">Esqueceu a senha? Peça ao gestor da sua escola para redefini-la.</p>
</x-layouts.guest>
