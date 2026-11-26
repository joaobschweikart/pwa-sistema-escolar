<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Disciplina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DisciplinaController extends Controller
{
    public function index(): View
    {
        $this->authorize('create', Disciplina::class);

        return view('gestao.disciplinas.index', ['disciplinas' => Disciplina::query()->withCount('alocacoes')->orderBy('nome')->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', Disciplina::class);

        return view('gestao.disciplinas.form', ['disciplina' => new Disciplina]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Disciplina::class);
        Disciplina::query()->create($this->validar($request));

        return redirect()->route('gestao.disciplinas.index')->with('status', 'Disciplina cadastrada.');
    }

    public function edit(Disciplina $disciplina): View
    {
        $this->authorize('update', $disciplina);

        return view('gestao.disciplinas.form', ['disciplina' => $disciplina]);
    }

    public function update(Request $request, Disciplina $disciplina): RedirectResponse
    {
        $this->authorize('update', $disciplina);
        $disciplina->update($this->validar($request, $disciplina));

        return redirect()->route('gestao.disciplinas.index')->with('status', 'Disciplina atualizada.');
    }

    public function destroy(Disciplina $disciplina): RedirectResponse
    {
        $this->authorize('delete', $disciplina);

        if ($disciplina->alocacoes()->exists()) {
            return back()->withErrors(['geral' => 'A disciplina está vinculada a turmas e não pode ser excluída.']);
        }

        $disciplina->delete();

        return redirect()->route('gestao.disciplinas.index')->with('status', 'Disciplina excluída.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Disciplina $disciplina = null): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('disciplinas', 'codigo')->ignore($disciplina?->id)],
            'carga_horaria' => ['nullable', 'integer', 'min:1', 'max:2000'],
        ]);
    }
}
