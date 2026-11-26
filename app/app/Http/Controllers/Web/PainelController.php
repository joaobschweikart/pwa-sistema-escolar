<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\AuditoriaNota;
use App\Infrastructure\Persistence\Models\ConflitoNota;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Turma;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PainelController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        if ($usuario->ehProfessor()) {
            return redirect()->route('professor.app');
        }

        $daEscola = fn (Builder $q) => $usuario->ehAdmin() ? $q : $q->where('escola_id', $usuario->escola_id);

        return view('painel', [
            'indicadores' => [
                ['rotulo' => 'Escolas', 'valor' => $usuario->ehAdmin() ? Escola::query()->count() : 1, 'rota' => $usuario->ehAdmin() ? route('gestao.escolas.index') : null],
                ['rotulo' => 'Turmas', 'valor' => $daEscola(Turma::query())->count(), 'rota' => route('gestao.turmas.index')],
                ['rotulo' => 'Alunos', 'valor' => $daEscola(Aluno::query())->count(), 'rota' => route('gestao.alunos.index')],
                ['rotulo' => 'Professores', 'valor' => $daEscola(User::query()->where('papel', 'professor'))->count(), 'rota' => route('gestao.usuarios.index')],
            ],
            'conflitosPendentes' => ConflitoNota::query()->visiveisPara($usuario)->where('status', 'pendente')->count(),
            'auditoria' => AuditoriaNota::query()
                ->when(! $usuario->ehAdmin(), fn (Builder $q) => $q->whereHas('avaliacao.alocacao.turma', fn (Builder $t) => $t->where('escola_id', $usuario->escola_id)))
                ->with(['usuario', 'avaliacao.alocacao.turma', 'matricula.aluno'])
                ->orderByDesc('ocorrido_em')
                ->limit(6)
                ->get(),
        ]);
    }
}
