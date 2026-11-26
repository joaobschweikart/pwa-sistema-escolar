<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditoriaNotaResource;
use App\Infrastructure\Persistence\Models\AuditoriaNota;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Conflitos e auditoria
 */
class AuditoriaController extends Controller
{
    /**
     * Auditoria de notas
     *
     * Histórico de alterações de nota (quem, quando, valor anterior e novo).
     * Filtre por `avaliacao_id` ou `matricula_id`.
     */
    public function notas(Request $request): AnonymousResourceCollection
    {
        /** @var User $usuario */
        $usuario = $request->user();
        abort_if($usuario->ehProfessor(), 403);

        $registros = AuditoriaNota::query()
            ->when(! $usuario->ehAdmin(), fn (Builder $q) => $q->whereHas(
                'avaliacao.alocacao.turma',
                fn (Builder $t) => $t->where('escola_id', $usuario->escola_id),
            ))
            ->when($request->filled('avaliacao_id'), fn (Builder $q) => $q->where('avaliacao_id', $request->integer('avaliacao_id')))
            ->when($request->filled('matricula_id'), fn (Builder $q) => $q->where('matricula_id', $request->integer('matricula_id')))
            ->with(['usuario', 'avaliacao', 'matricula.aluno'])
            ->orderByDesc('ocorrido_em')
            ->orderByDesc('id')
            ->paginate(50);

        return AuditoriaNotaResource::collection($registros);
    }
}
