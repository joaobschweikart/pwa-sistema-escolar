<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Domain\Shared\Papel;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\Disciplina;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Matricula;
use App\Infrastructure\Persistence\Models\Turma;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Turmas, matrículas e vínculos professor ↔ disciplina.
 */
class TurmaController extends Controller
{
    public function index(Request $request): View
    {
        return view('gestao.turmas.index', [
            'turmas' => Turma::query()
                ->visiveisPara($this->usuario($request))
                ->with('escola')
                ->withCount(['matriculas' => fn ($q) => $q->ativas(), 'alocacoes'])
                ->orderBy('escola_id')->orderBy('nome')
                ->paginate(20),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Turma::class);

        return view('gestao.turmas.form', ['turma' => new Turma(['ano_letivo' => (int) date('Y'), 'turno' => 'matutino']), 'escolas' => $this->escolas($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Turma::class);
        $turma = Turma::query()->create($this->validar($request));

        return redirect()->route('gestao.turmas.show', $turma)->with('status', 'Turma cadastrada.');
    }

    public function show(Turma $turma): View
    {
        $this->authorize('update', $turma);
        $turma->load([
            'escola',
            'matriculas' => fn ($q) => $q->with('aluno')->orderBy('numero_chamada'),
            'alocacoes' => fn ($q) => $q->with(['disciplina', 'professor']),
        ]);

        $matriculados = $turma->matriculas->pluck('aluno_id');

        return view('gestao.turmas.show', [
            'turma' => $turma,
            'alunosDisponiveis' => Aluno::query()->where('escola_id', $turma->escola_id)->whereNotIn('id', $matriculados)->orderBy('nome')->get(),
            'disciplinasDisponiveis' => Disciplina::query()->whereNotIn('id', $turma->alocacoes->pluck('disciplina_id'))->orderBy('nome')->get(),
            'professores' => User::query()->where('papel', Papel::Professor)->where('escola_id', $turma->escola_id)->where('ativo', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Request $request, Turma $turma): View
    {
        $this->authorize('update', $turma);

        return view('gestao.turmas.form', ['turma' => $turma, 'escolas' => $this->escolas($request)]);
    }

    public function update(Request $request, Turma $turma): RedirectResponse
    {
        $this->authorize('update', $turma);
        $turma->update($this->validar($request, $turma));

        return redirect()->route('gestao.turmas.show', $turma)->with('status', 'Turma atualizada.');
    }

    public function destroy(Turma $turma): RedirectResponse
    {
        $this->authorize('delete', $turma);

        if ($turma->alocacoes()->whereHas('aulas')->exists()) {
            return back()->withErrors(['geral' => 'A turma possui aulas registradas e não pode ser excluída.']);
        }

        $turma->delete();

        return redirect()->route('gestao.turmas.index')->with('status', 'Turma excluída.');
    }

    public function matricular(Request $request, Turma $turma): RedirectResponse
    {
        $this->authorize('update', $turma);
        $dados = $request->validate([
            'aluno_id' => ['required', Rule::exists('alunos', 'id')->where('escola_id', $turma->escola_id), Rule::unique('matriculas')->where('turma_id', $turma->id)],
        ]);

        $turma->matriculas()->create([
            'aluno_id' => $dados['aluno_id'],
            'numero_chamada' => (int) $turma->matriculas()->max('numero_chamada') + 1,
            'situacao' => 'ativa',
            'data_matricula' => now()->toDateString(),
        ]);

        return back()->with('status', 'Aluno matriculado.');
    }

    public function alterarMatricula(Request $request, Matricula $matricula): RedirectResponse
    {
        $this->authorize('update', $matricula->turma);
        $matricula->update($request->validate(['situacao' => ['required', Rule::in(array_keys(Matricula::SITUACOES))]]));

        return back()->with('status', 'Situação da matrícula atualizada.');
    }

    public function vincular(Request $request, Turma $turma): RedirectResponse
    {
        $this->authorize('update', $turma);
        $dados = $request->validate([
            'disciplina_id' => ['required', 'exists:disciplinas,id', Rule::unique('alocacoes')->where('turma_id', $turma->id)],
            'professor_id' => ['required', Rule::exists('users', 'id')->where('papel', Papel::Professor->value)->where('escola_id', $turma->escola_id)],
        ]);

        $turma->alocacoes()->create($dados);

        return back()->with('status', 'Professor vinculado à disciplina.');
    }

    public function desvincular(Alocacao $alocacao): RedirectResponse
    {
        $this->authorize('delete', $alocacao);

        if ($alocacao->aulas()->exists() || $alocacao->avaliacoes()->exists()) {
            return back()->withErrors(['geral' => 'O vínculo já possui diário registrado e não pode ser removido.']);
        }

        $alocacao->delete();

        return back()->with('status', 'Vínculo removido.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Turma $turma = null): array
    {
        $usuario = $this->usuario($request);
        $dados = $request->validate([
            'escola_id' => [$usuario->ehAdmin() ? 'required' : 'nullable', 'exists:escolas,id'],
            'nome' => ['required', 'string', 'max:60'],
            'serie' => ['required', 'string', 'max:40'],
            'turno' => ['required', Rule::in(array_keys(Turma::TURNOS))],
            'ano_letivo' => ['required', 'integer', 'between:2000,2100'],
        ]);

        if (! $usuario->ehAdmin()) {
            $dados['escola_id'] = $usuario->escola_id;
        }

        return $dados;
    }

    /** @return Collection<int, Escola> */
    private function escolas(Request $request): Collection
    {
        $usuario = $this->usuario($request);

        return $usuario->ehAdmin() ? Escola::query()->orderBy('nome')->get() : Escola::query()->whereKey($usuario->escola_id)->get();
    }

    private function usuario(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
