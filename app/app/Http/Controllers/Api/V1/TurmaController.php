<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatriculaResource;
use App\Http\Resources\TurmaResource;
use App\Infrastructure\Persistence\Models\Turma;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Cadastros
 */
class TurmaController extends Controller
{
    /**
     * Listar turmas
     *
     * Professor vê as turmas em que leciona; gestor, as da escola; admin, todas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $usuario */
        $usuario = $request->user();

        $turmas = Turma::query()
            ->visiveisPara($usuario)
            ->with('escola')
            ->withCount('matriculas')
            ->orderBy('nome')
            ->paginate(25);

        return TurmaResource::collection($turmas);
    }

    /** Detalhar turma */
    public function show(Turma $turma): TurmaResource
    {
        $this->authorize('view', $turma);

        return new TurmaResource($turma->load('escola')->loadCount('matriculas'));
    }

    /** Alunos matriculados (matrículas ativas) */
    public function alunos(Turma $turma): AnonymousResourceCollection
    {
        $this->authorize('view', $turma);

        return MatriculaResource::collection(
            $turma->matriculas()->ativas()->with('aluno')->orderBy('numero_chamada')->get()
        );
    }
}
