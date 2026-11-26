<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\AuditoriaNota;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function __invoke(Request $request): View
    {
        /** @var User $usuario */
        $usuario = $request->user();

        return view('gestao.auditoria.index', [
            'registros' => AuditoriaNota::query()
                ->when(! $usuario->ehAdmin(), fn (Builder $q) => $q->whereHas('avaliacao.alocacao.turma', fn (Builder $t) => $t->where('escola_id', $usuario->escola_id)))
                ->when($request->filled('origem'), fn (Builder $q) => $q->where('origem', $request->string('origem')->toString()))
                ->with(['usuario', 'avaliacao.alocacao.turma', 'avaliacao.alocacao.disciplina', 'matricula.aluno'])
                ->orderByDesc('ocorrido_em')->orderByDesc('id')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }
}
