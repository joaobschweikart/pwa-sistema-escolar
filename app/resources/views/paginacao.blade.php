@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="btn-secundario opacity-50">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn-secundario">Anterior</a>
        @endif
        <span class="text-sm text-slate-600">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn-secundario">Próxima</a>
        @else
            <span class="btn-secundario opacity-50">Próxima</span>
        @endif
    </nav>
@endif
