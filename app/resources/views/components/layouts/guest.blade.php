@props(['titulo' => null])
<x-layouts.base :titulo="$titulo ?? null">
    <main class="flex min-h-full flex-col items-center justify-center px-4 py-10">
        <div class="mb-6 flex flex-col items-center text-center">
            <img src="/icons/icon.svg" alt="" class="size-16">
            <p class="mt-3 text-lg font-bold text-marca-800">Diário Escolar Municipal</p>
            <p class="text-sm text-slate-600">Diário de classe que funciona sem internet</p>
        </div>
        <div class="cartao w-full max-w-sm p-6">
            {{ $slot }}
        </div>
    </main>
</x-layouts.base>
