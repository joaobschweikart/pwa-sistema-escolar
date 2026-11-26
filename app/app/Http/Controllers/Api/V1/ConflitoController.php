<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Sincronizacao\ResolverConflitoNota;
use App\Domain\Sincronizacao\ResolucaoConflito;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ResolverConflitoRequest;
use App\Http\Resources\ConflitoNotaResource;
use App\Http\Support\Contexto;
use App\Infrastructure\Persistence\Models\ConflitoNota;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Conflitos e auditoria
 */
class ConflitoController extends Controller
{
    /**
     * Listar conflitos de nota
     *
     * Fila de conflitos de nota da escola do gestor (admin vê todos).
     * Filtre por `status` (`pendente` ou `resolvido`).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ConflitoNota::class);
        /** @var User $usuario */
        $usuario = $request->user();

        return ConflitoNotaResource::collection(
            ConflitoNota::query()
                ->visiveisPara($usuario)
                ->where('status', $request->string('status', 'pendente')->toString())
                ->with(['avaliacao.alocacao.turma', 'avaliacao.alocacao.disciplina', 'matricula.aluno', 'professor', 'resolvidoPor'])
                ->latest()
                ->paginate(25)
        );
    }

    /**
     * Resolver conflito de nota
     *
     * `manter_atual` mantém o valor do servidor; `aplicar_proposto` aplica o valor
     * enviado pelo professor (alteração auditada).
     */
    public function resolver(ResolverConflitoRequest $request, ConflitoNota $conflito, ResolverConflitoNota $resolver): JsonResponse
    {
        $this->authorize('resolver', $conflito);
        /** @var User $usuario */
        $usuario = $request->user();

        $resultado = $resolver->executar(Contexto::de($usuario), $conflito->id, ResolucaoConflito::from($request->string('resolucao')->toString()));

        return response()->json([
            'resultado' => $resultado->paraArray(),
            'conflito' => new ConflitoNotaResource($conflito->refresh()->load(['avaliacao.alocacao.turma', 'avaliacao.alocacao.disciplina', 'matricula.aluno', 'professor', 'resolvidoPor'])),
        ]);
    }
}
