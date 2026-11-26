<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Application\Sincronizacao\ResolverConflitoNota;
use App\Domain\Sincronizacao\ResolucaoConflito;
use App\Http\Controllers\Controller;
use App\Http\Support\Contexto;
use App\Infrastructure\Persistence\Models\ConflitoNota;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tela do gestor para decidir os conflitos de nota vindos da sincronização.
 */
class ConflitoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ConflitoNota::class);
        /** @var User $usuario */
        $usuario = $request->user();
        $status = $request->string('status', 'pendente')->toString() === 'resolvido' ? 'resolvido' : 'pendente';

        return view('gestao.conflitos.index', [
            'status' => $status,
            'conflitos' => ConflitoNota::query()
                ->visiveisPara($usuario)
                ->where('status', $status)
                ->with(['avaliacao.alocacao.turma', 'avaliacao.alocacao.disciplina', 'matricula.aluno', 'professor', 'resolvidoPor'])
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function resolver(Request $request, ConflitoNota $conflito, ResolverConflitoNota $resolver): RedirectResponse
    {
        $this->authorize('resolver', $conflito);
        $dados = $request->validate(['resolucao' => ['required', Rule::enum(ResolucaoConflito::class)]]);
        /** @var User $usuario */
        $usuario = $request->user();

        $resultado = $resolver->executar(Contexto::de($usuario), $conflito->id, ResolucaoConflito::from($dados['resolucao']));

        return back()->with('status', $resultado->mensagem);
    }
}
