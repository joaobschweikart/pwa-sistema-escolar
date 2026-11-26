@php $origens = ['api' => 'API/online', 'sincronizacao' => 'Sincronização offline', 'resolucao_conflito' => 'Resolução de conflito']; @endphp
@if ($registros->isEmpty())
    <x-vazio texto="Nenhuma alteração de nota registrada." />
@else
    <div class="cartao hidden overflow-hidden md:block">
        <table class="tabela">
            <thead><tr><th>Quando</th><th>Quem</th><th>Aluno</th><th>Avaliação</th><th class="text-right">De → para</th><th>Origem</th></tr></thead>
            <tbody>
            @foreach ($registros as $r)
                <tr>
                    <td class="whitespace-nowrap text-slate-600">{{ $r->ocorrido_em->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->usuario->name }}</td>
                    <td>{{ $r->matricula->aluno->nome }}</td>
                    <td>{{ $r->avaliacao->titulo }} <span class="text-slate-500">· {{ $r->avaliacao->alocacao->turma->nome }}</span></td>
                    <td class="text-right font-mono">{{ $r->valor_anterior !== null ? number_format($r->valor_anterior, 1, ',', '') : '—' }} → <strong>{{ number_format($r->valor_novo, 1, ',', '') }}</strong></td>
                    <td><span class="selo bg-slate-100 text-slate-700">{{ $origens[$r->origem] ?? $r->origem }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <ul class="space-y-3 md:hidden">
        @foreach ($registros as $r)
            <li class="cartao p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $r->matricula->aluno->nome }}</p>
                        <p class="text-sm text-slate-600">{{ $r->avaliacao->titulo }} · {{ $r->avaliacao->alocacao->turma->nome }}</p>
                    </div>
                    <p class="font-mono text-sm whitespace-nowrap">{{ $r->valor_anterior !== null ? number_format($r->valor_anterior, 1, ',', '') : '—' }} → <strong>{{ number_format($r->valor_novo, 1, ',', '') }}</strong></p>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ $r->ocorrido_em->format('d/m/Y H:i') }} · {{ $r->usuario->name }} · {{ $origens[$r->origem] ?? $r->origem }}</p>
            </li>
        @endforeach
    </ul>
@endif
