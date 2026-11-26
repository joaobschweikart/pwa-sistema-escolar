<x-layouts.app titulo="Conflitos de nota">
    <x-cabecalho titulo="Conflitos de nota" subtitulo="Notas lançadas offline que divergem de uma alteração feita depois no servidor. Elas não são aplicadas até a sua decisão." />
    <div class="mb-4 flex gap-2">
        <a href="{{ route('gestao.conflitos.index') }}" @class(['btn px-4', 'bg-marca-700 text-white' => $status === 'pendente', 'btn-secundario' => $status !== 'pendente'])>Pendentes</a>
        <a href="{{ route('gestao.conflitos.index', ['status' => 'resolvido']) }}" @class(['btn px-4', 'bg-marca-700 text-white' => $status === 'resolvido', 'btn-secundario' => $status !== 'resolvido'])>Resolvidos</a>
    </div>

    @if ($conflitos->isEmpty())
        <x-vazio :texto="$status === 'pendente' ? 'Nenhum conflito pendente. 🎉' : 'Nenhum conflito resolvido ainda.'" />
    @else
        <ul class="grid gap-3 lg:grid-cols-2">
            @foreach ($conflitos as $c)
                <li class="cartao p-4" data-conflito="{{ $c->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $c->matricula->aluno->nome }}</p>
                            <p class="text-sm text-slate-600">{{ $c->avaliacao->titulo }} · {{ $c->avaliacao->alocacao->disciplina->nome }} · {{ $c->avaliacao->alocacao->turma->nome }}</p>
                        </div>
                        <span class="selo {{ $c->status === 'pendente' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700' }}">{{ ucfirst($c->status) }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <dt class="text-xs text-slate-500">Valor atual no servidor</dt>
                            <dd class="text-2xl font-bold">{{ number_format($c->valor_servidor, 1, ',', '') }}</dd>
                            <dd class="text-xs text-slate-500">versão {{ $c->versao_servidor }}</dd>
                        </div>
                        <div class="rounded-lg bg-marca-50 p-3">
                            <dt class="text-xs text-slate-500">Proposto por {{ str($c->professor->name)->before(' (') }}</dt>
                            <dd class="text-2xl font-bold text-marca-800">{{ number_format($c->valor_proposto, 1, ',', '') }}</dd>
                            <dd class="text-xs text-slate-500">offline em {{ $c->proposto_em->format('d/m H:i') }} (base v{{ $c->versao_base }})</dd>
                        </div>
                    </dl>
                    @if ($c->status === 'pendente')
                        @can('resolver', $c)
                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <form method="POST" action="{{ route('gestao.conflitos.resolver', $c) }}">
                                    @csrf <input type="hidden" name="resolucao" value="manter_atual">
                                    <button class="btn-secundario w-full">Manter {{ number_format($c->valor_servidor, 1, ',', '') }}</button>
                                </form>
                                <form method="POST" action="{{ route('gestao.conflitos.resolver', $c) }}">
                                    @csrf <input type="hidden" name="resolucao" value="aplicar_proposto">
                                    <button class="btn-primario w-full">Aplicar {{ number_format($c->valor_proposto, 1, ',', '') }}</button>
                                </form>
                            </div>
                        @endcan
                    @else
                        <p class="mt-3 text-sm text-slate-600">
                            {{ \App\Domain\Sincronizacao\ResolucaoConflito::tryFrom((string) $c->resolucao)?->rotulo() }} — por {{ $c->resolvidoPor?->name }} em {{ $c->resolvido_em?->format('d/m/Y H:i') }}.
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $conflitos->links() }}</div>
    @endif
</x-layouts.app>
