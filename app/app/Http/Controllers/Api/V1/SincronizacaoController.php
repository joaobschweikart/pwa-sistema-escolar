<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Sincronizacao\ProcessarLoteSincronizacao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SincronizarRequest;
use App\Http\Support\Contexto;
use App\Http\Sync\MapeadorOperacoes;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @tags Sincronização offline
 */
class SincronizacaoController extends Controller
{
    /**
     * Sincronizar lote offline
     *
     * Recebe a fila de lançamentos feita sem conexão pela PWA do professor.
     *
     * - Cada operação tem um `uuid` gerado no cliente: reenviar o mesmo lote não
     *   produz efeito duplicado (`duplicada: true`).
     * - `versao_base` é a versão do registro que estava em cache no dispositivo.
     * - Aula e frequência: conflitos resolvidos por last-write-wins (`ocorrido_em`).
     * - Nota: conflito **não é aplicado**; vai para a fila de conflitos do gestor.
     *
     * Tipos: `aula.registrar`, `frequencia.registrar`, `nota.lancar`.
     */
    public function __invoke(SincronizarRequest $request, MapeadorOperacoes $mapeador, ProcessarLoteSincronizacao $processar): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        /** @var list<array<string, mixed>> $operacoes */
        $operacoes = $request->validated('operacoes');

        $resultados = $processar->executar(Contexto::de($usuario), $mapeador->mapear($operacoes));

        $resumo = ['aplicadas' => 0, 'descartadas' => 0, 'conflitos' => 0, 'rejeitadas' => 0, 'duplicadas' => 0];
        foreach ($resultados as $r) {
            $chave = match ($r['status']) {
                'aplicada' => 'aplicadas',
                'descartada' => 'descartadas',
                'conflito' => 'conflitos',
                default => 'rejeitadas',
            };
            $resumo[$chave]++;
            $resumo['duplicadas'] += $r['duplicada'] ? 1 : 0;
        }

        return response()->json([
            'resultados' => $resultados,
            'resumo' => $resumo,
            'servidor_em' => now()->toIso8601String(),
        ]);
    }
}
