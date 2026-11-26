<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\Avaliacao;
use App\Infrastructure\Persistence\Models\Frequencia;
use App\Infrastructure\Persistence\Models\Matricula;
use App\Infrastructure\Persistence\Models\Nota;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Sincronização offline
 */
class ProfessorController extends Controller
{
    /**
     * Dados para uso offline
     *
     * Retorna somente as turmas/disciplinas do professor autenticado, com alunos,
     * aulas, frequência, avaliações e notas (com versões), para cache no IndexedDB.
     */
    public function dados(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        abort_unless($usuario->ehProfessor(), 403, 'Somente professores usam o modo offline.');

        $alocacoes = Alocacao::query()
            ->where('professor_id', $usuario->id)
            ->with([
                'turma.escola',
                'disciplina',
                'turma.matriculas' => fn ($q) => $q->ativas()->with('aluno:id,nome')->orderBy('numero_chamada'),
                'aulas' => fn ($q) => $q->with('frequencias')->orderByDesc('data'),
                'avaliacoes' => fn ($q) => $q->with('notas')->orderBy('data'),
            ])
            ->get();

        return response()->json([
            'usuario' => ['id' => $usuario->id, 'nome' => $usuario->name],
            'gerado_em' => now()->toIso8601String(),
            'alocacoes' => $alocacoes->map(fn (Alocacao $a): array => $this->alocacao($a))->values()->all(),
            'criterios' => ['media_minima' => config('dem.media_minima'), 'frequencia_minima' => config('dem.frequencia_minima')],
        ]);
    }

    /** @return array<string, mixed> */
    private function alocacao(Alocacao $a): array
    {
        return [
            'id' => $a->id,
            'turma' => ['id' => $a->turma->id, 'nome' => $a->turma->nome, 'turno' => $a->turma->turno, 'escola' => $a->turma->escola->nome],
            'disciplina' => ['id' => $a->disciplina->id, 'nome' => $a->disciplina->nome],
            'alunos' => $a->turma->matriculas->map(fn (Matricula $m): array => [
                'matricula_id' => $m->id,
                'nome' => $m->aluno->nome,
                'numero_chamada' => $m->numero_chamada,
            ])->values()->all(),
            'aulas' => $a->aulas->map(fn (Aula $aula): array => $this->aula($aula))->values()->all(),
            'avaliacoes' => $a->avaliacoes->map(fn (Avaliacao $av): array => $this->avaliacao($av))->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function aula(Aula $aula): array
    {
        return [
            'id' => $aula->id,
            'data' => $aula->data->toDateString(),
            'conteudo' => $aula->conteudo,
            'observacoes' => $aula->observacoes,
            'versao' => $aula->versao,
            'frequencias' => $aula->frequencias->map(fn (Frequencia $f): array => [
                'matricula_id' => $f->matricula_id, 'presente' => $f->presente, 'versao' => $f->versao,
            ])->values()->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function avaliacao(Avaliacao $av): array
    {
        return [
            'id' => $av->id,
            'titulo' => $av->titulo,
            'data' => $av->data->toDateString(),
            'periodo' => $av->periodo,
            'peso' => $av->peso,
            'notas' => $av->notas->map(fn (Nota $n): array => [
                'matricula_id' => $n->matricula_id, 'valor' => $n->valor, 'versao' => $n->versao,
            ])->values()->all(),
        ];
    }
}
