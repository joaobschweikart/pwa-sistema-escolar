<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Avaliacao\GerarBoletim;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlocacaoResource;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Diário
 */
class AlocacaoController extends Controller
{
    /**
     * Listar turmas/disciplinas (vínculos)
     *
     * Para o professor, retorna somente os seus vínculos com turmas e disciplinas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $usuario */
        $usuario = $request->user();

        return AlocacaoResource::collection(
            Alocacao::query()->visiveisPara($usuario)->with(['turma.escola', 'disciplina', 'professor'])->get()
        );
    }

    /** Detalhar vínculo turma/disciplina */
    public function show(Alocacao $alocacao): AlocacaoResource
    {
        $this->authorize('view', $alocacao);

        return new AlocacaoResource($alocacao->load(['turma.escola', 'disciplina', 'professor']));
    }

    /**
     * Boletim da turma na disciplina
     *
     * Média ponderada pelos pesos das avaliações, frequência (%) e situação de cada aluno.
     * Critérios: média mínima e frequência mínima configuráveis (padrão 6,0 e 75%).
     */
    public function boletim(Request $request, Alocacao $alocacao, GerarBoletim $boletim): JsonResponse
    {
        $this->authorize('view', $alocacao);
        $periodo = $request->integer('periodo') ?: null;

        return response()->json([
            'alocacao' => new AlocacaoResource($alocacao->load(['turma.escola', 'disciplina', 'professor'])),
            'periodo' => $periodo,
            'criterios' => ['media_minima' => config('dem.media_minima'), 'frequencia_minima' => config('dem.frequencia_minima')],
            'alunos' => $boletim->executar($alocacao->id, $periodo),
        ]);
    }
}
