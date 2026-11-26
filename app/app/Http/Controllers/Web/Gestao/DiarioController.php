<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Application\Avaliacao\GerarBoletim;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Alocacao;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta do diário pelo gestor: aulas, avaliações e boletim de uma turma/disciplina.
 */
class DiarioController extends Controller
{
    public function __invoke(Request $request, Alocacao $alocacao, GerarBoletim $boletim): View
    {
        $this->authorize('view', $alocacao);
        $periodo = $request->integer('periodo') ?: null;

        return view('gestao.diario.show', [
            'alocacao' => $alocacao->load(['turma.escola', 'disciplina', 'professor']),
            'aulas' => $alocacao->aulas()->withCount(['frequencias as presentes_count' => fn ($q) => $q->where('presente', true), 'frequencias'])->orderByDesc('data')->limit(30)->get(),
            'avaliacoes' => $alocacao->avaliacoes()->withCount('notas')->orderBy('data')->get(),
            'boletim' => $boletim->executar($alocacao->id, $periodo),
            'periodo' => $periodo,
        ]);
    }
}
