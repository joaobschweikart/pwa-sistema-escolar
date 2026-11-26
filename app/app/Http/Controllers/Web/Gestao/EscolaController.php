<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EscolaRequest;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Municipio;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EscolaController extends Controller
{
    public function index(): View
    {
        $this->authorize('create', Escola::class);

        return view('gestao.escolas.index', [
            'escolas' => Escola::query()->with('municipio')->withCount(['turmas', 'alunos'])->orderBy('nome')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Escola::class);

        return view('gestao.escolas.form', ['escola' => new Escola, 'municipios' => Municipio::query()->orderBy('nome')->get()]);
    }

    public function store(EscolaRequest $request): RedirectResponse
    {
        $this->authorize('create', Escola::class);
        Escola::query()->create($request->validated());

        return redirect()->route('gestao.escolas.index')->with('status', 'Escola cadastrada.');
    }

    public function edit(Escola $escola): View
    {
        $this->authorize('update', $escola);

        return view('gestao.escolas.form', ['escola' => $escola, 'municipios' => Municipio::query()->orderBy('nome')->get()]);
    }

    public function update(EscolaRequest $request, Escola $escola): RedirectResponse
    {
        $this->authorize('update', $escola);
        $escola->update($request->validated());

        return redirect()->route('gestao.escolas.index')->with('status', 'Escola atualizada.');
    }

    public function destroy(Escola $escola): RedirectResponse
    {
        $this->authorize('delete', $escola);

        if ($escola->turmas()->exists() || $escola->alunos()->exists()) {
            return back()->withErrors(['geral' => 'A escola possui turmas ou alunos e não pode ser excluída.']);
        }

        $escola->delete();

        return redirect()->route('gestao.escolas.index')->with('status', 'Escola excluída.');
    }
}
