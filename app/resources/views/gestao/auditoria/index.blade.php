<x-layouts.app titulo="Auditoria de notas">
    <x-cabecalho titulo="Auditoria de notas" subtitulo="Toda alteração de nota: quem alterou, quando e os valores anterior e novo. Registros imutáveis." />
    <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end">
        <div class="sm:w-72">
            <x-selecao nome="origem" rotulo="Origem" :valor="request('origem')" vazio="Todas" :opcoes="['api' => 'API/online', 'sincronizacao' => 'Sincronização offline', 'resolucao_conflito' => 'Resolução de conflito']" />
        </div>
        <button class="btn-secundario">Filtrar</button>
    </form>
    @include('gestao.auditoria._lista')
    <div class="mt-4">{{ $registros->links() }}</div>
</x-layouts.app>
