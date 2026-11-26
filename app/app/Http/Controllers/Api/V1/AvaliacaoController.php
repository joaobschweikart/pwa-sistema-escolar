<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Avaliacao\LancarNota;
use App\Application\Avaliacao\LancarNotaComando;
use App\Domain\Avaliacao\OrigemAlteracao;
use App\Domain\Sincronizacao\StatusOperacao;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AvaliacaoRequest;
use App\Http\Requests\Api\NotasRequest;
use App\Http\Resources\AvaliacaoResource;
use App\Http\Support\Contexto;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Avaliacao;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @tags Avaliações e notas
 */
class AvaliacaoController extends Controller
{
    /** Listar avaliações da turma/disciplina (com notas) */
    public function index(Alocacao $alocacao): AnonymousResourceCollection
    {
        $this->authorize('view', $alocacao);

        return AvaliacaoResource::collection($alocacao->avaliacoes()->with('notas')->orderBy('data')->get());
    }

    /** Criar avaliação (somente o professor vinculado) */
    public function store(AvaliacaoRequest $request, Alocacao $alocacao): JsonResponse
    {
        $this->authorize('registrarDiario', $alocacao);
        $avaliacao = $alocacao->avaliacoes()->create($request->validated());

        return (new AvaliacaoResource($avaliacao->load('notas')))->response()->setStatusCode(201);
    }

    /** Notas da avaliação */
    public function notas(Avaliacao $avaliacao): AvaliacaoResource
    {
        $this->authorize('view', $avaliacao->alocacao);

        return new AvaliacaoResource($avaliacao->load('notas'));
    }

    /**
     * Lançar notas
     *
     * Informe a `versao` lida de cada nota já existente. Se a nota foi alterada
     * depois disso, ela não é sobrescrita: a resposta é 409 com o valor atual.
     * Toda alteração aplicada é registrada na auditoria.
     */
    public function lancarNotas(NotasRequest $request, Avaliacao $avaliacao, LancarNota $lancar): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        $contexto = Contexto::de($usuario);
        /** @var list<array{matricula_id: int, valor: float, versao?: int|null}> $notas */
        $notas = $request->validated('notas');

        $resultados = DB::transaction(fn () => array_map(fn (array $n) => [
            'matricula_id' => (int) $n['matricula_id'],
        ] + $lancar->executar($contexto, new LancarNotaComando(
            avaliacaoId: $avaliacao->id,
            matriculaId: (int) $n['matricula_id'],
            valor: (float) $n['valor'],
            versaoBase: (int) ($n['versao'] ?? 0),
            ocorridoEm: now()->toDateTimeImmutable(),
            origem: OrigemAlteracao::Api,
        ))->paraArray(), $notas));

        $conflitos = array_filter($resultados, fn (array $r) => $r['status'] === StatusOperacao::Conflito->value);

        return response()->json([
            'resultados' => $resultados,
            'avaliacao' => new AvaliacaoResource($avaliacao->load('notas')),
        ], $conflitos === [] ? 200 : 409);
    }
}
