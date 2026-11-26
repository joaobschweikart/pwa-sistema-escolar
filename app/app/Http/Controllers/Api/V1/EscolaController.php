<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EscolaRequest;
use App\Http\Resources\EscolaResource;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Cadastros
 */
class EscolaController extends Controller
{
    /**
     * Listar escolas
     *
     * Admin vê todas as escolas do município; gestor vê somente a sua.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Escola::class);
        /** @var User $usuario */
        $usuario = $request->user();

        $escolas = Escola::query()
            ->with('municipio')
            ->withCount('turmas')
            ->when(! $usuario->ehAdmin(), fn ($q) => $q->whereKey($usuario->escola_id))
            ->orderBy('nome')
            ->paginate(25);

        return EscolaResource::collection($escolas);
    }

    /** Cadastrar escola (somente Admin da Secretaria) */
    public function store(EscolaRequest $request): EscolaResource
    {
        $this->authorize('create', Escola::class);

        return new EscolaResource(Escola::query()->create($request->validated())->load('municipio'));
    }

    /** Detalhar escola */
    public function show(Escola $escola): EscolaResource
    {
        $this->authorize('view', $escola);

        return new EscolaResource($escola->load('municipio')->loadCount('turmas'));
    }

    /** Atualizar escola (somente Admin da Secretaria) */
    public function update(EscolaRequest $request, Escola $escola): EscolaResource
    {
        $this->authorize('update', $escola);
        $escola->update($request->validated());

        return new EscolaResource($escola->load('municipio'));
    }

    /** Excluir escola (somente Admin da Secretaria) */
    public function destroy(Escola $escola): Response
    {
        $this->authorize('delete', $escola);
        $escola->delete();

        return response()->noContent();
    }
}
