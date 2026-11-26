<x-layouts.guest titulo="Recuperar senha">
    <h1 class="mb-2 text-lg font-semibold">Recuperar senha</h1>
    <p class="mb-4 text-sm text-slate-600">Informe seu e-mail. Enviaremos um link para você criar uma nova senha.</p>
    <x-mensagens />
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-campo nome="email" rotulo="E-mail" tipo="email" autocomplete="username" inputmode="email" autofocus obrigatorio />
        <button type="submit" class="btn-primario w-full">Enviar link</button>
    </form>
    <a href="{{ route('login') }}" class="mt-4 flex min-h-11 items-center justify-center text-sm font-medium text-marca-700 hover:underline">Voltar para o login</a>
</x-layouts.guest>
