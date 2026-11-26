<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlunoController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Aluno::class);

        return view('gestao.alunos.index', [
            'alunos' => Aluno::query()
                ->visiveisPara($this->usuario($request))
                ->when($request->filled('busca'), fn ($q) => $q->where('nome', 'like', '%'.$request->string('busca').'%'))
                ->with(['escola', 'matriculas' => fn ($q) => $q->ativas()->with('turma')])
                ->orderBy('nome')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Aluno::class);

        return view('gestao.alunos.form', ['aluno' => new Aluno, 'escolas' => $this->escolas($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Aluno::class);
        Aluno::query()->create($this->validar($request));

        return redirect()->route('gestao.alunos.index')->with('status', 'Aluno cadastrado.');
    }

    public function edit(Request $request, Aluno $aluno): View
    {
        $this->authorize('update', $aluno);

        return view('gestao.alunos.form', ['aluno' => $aluno, 'escolas' => $this->escolas($request)]);
    }

    public function update(Request $request, Aluno $aluno): RedirectResponse
    {
        $this->authorize('update', $aluno);
        $aluno->update($this->validar($request, $aluno));

        return redirect()->route('gestao.alunos.index')->with('status', 'Aluno atualizado.');
    }

    public function destroy(Aluno $aluno): RedirectResponse
    {
        $this->authorize('delete', $aluno);

        if ($aluno->matriculas()->exists()) {
            return back()->withErrors(['geral' => 'O aluno possui matrículas e não pode ser excluído.']);
        }

        $aluno->delete();

        return redirect()->route('gestao.alunos.index')->with('status', 'Aluno excluído.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Aluno $aluno = null): array
    {
        $usuario = $this->usuario($request);
        $dados = $request->validate([
            'escola_id' => [$usuario->ehAdmin() ? 'required' : 'nullable', 'exists:escolas,id'],
            'nome' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('alunos', 'codigo')->ignore($aluno?->id)],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'responsavel' => ['nullable', 'string', 'max:255'],
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
