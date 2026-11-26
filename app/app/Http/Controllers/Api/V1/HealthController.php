<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Healthcheck
     *
     * Verifica a aplicação e a conexão com o banco `dem_db`. Usado pelo
     * Docker, pelo deploy (rollback automático) e pelo monitoramento.
     *
     * @unauthenticated
     */
    public function __invoke(): JsonResponse
    {
        $inicio = microtime(true);

        try {
            DB::select('select 1');
            $banco = 'ok';
        } catch (Throwable) {
            $banco = 'indisponivel';
        }

        $ok = $banco === 'ok';

        return response()->json([
            'status' => $ok ? 'ok' : 'degradado',
            'banco' => $banco,
            'versao' => config('dem.versao'),
            'tempo_ms' => round((microtime(true) - $inicio) * 1000, 1),
            'servidor_em' => now()->toIso8601String(),
        ], $ok ? 200 : 503);
    }
}
