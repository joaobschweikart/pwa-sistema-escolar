<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Avaliacao\Contracts\DadosBoletimRepository;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\Frequencia;
use App\Infrastructure\Persistence\Models\Matricula;
use App\Infrastructure\Persistence\Models\Nota;

final class EloquentDadosBoletimRepository implements DadosBoletimRepository
{
    public function matriculasAtivas(int $alocacaoId): array
    {
        $turmaId = Alocacao::query()->whereKey($alocacaoId)->value('turma_id');

        return array_values(Matricula::query()
            ->ativas()
            ->where('turma_id', $turmaId)
            ->with('aluno:id,nome')
            ->orderBy('numero_chamada')
            ->get()
            ->map(fn (Matricula $m): array => [
                'matricula_id' => $m->id,
                'aluno_id' => $m->aluno_id,
                'aluno' => $m->aluno->nome,
                'numero_chamada' => $m->numero_chamada,
            ])
            ->all());
    }

    public function notasComPeso(int $alocacaoId, ?int $periodo = null): array
    {
        return array_values(Nota::query()
            ->join('avaliacoes', 'avaliacoes.id', '=', 'notas.avaliacao_id')
            ->where('avaliacoes.alocacao_id', $alocacaoId)
            ->when($periodo !== null, fn ($q) => $q->where('avaliacoes.periodo', $periodo))
            ->get(['notas.matricula_id', 'notas.valor', 'avaliacoes.peso'])
            ->map(fn (Nota $n): array => [
                'matricula_id' => (int) $n->matricula_id,
                'valor' => (float) $n->valor,
                'peso' => (float) $n->getAttribute('peso'),
            ])
            ->all());
    }

    public function totalAulas(int $alocacaoId): int
    {
        return Aula::query()->where('alocacao_id', $alocacaoId)->count();
    }

    public function presencasPorMatricula(int $alocacaoId): array
    {
        /** @var array<int, int> $presencas */
        $presencas = Frequencia::query()
            ->join('aulas', 'aulas.id', '=', 'frequencias.aula_id')
            ->where('aulas.alocacao_id', $alocacaoId)
            ->where('frequencias.presente', true)
            ->groupBy('frequencias.matricula_id')
            ->selectRaw('frequencias.matricula_id, COUNT(*) as total')
            ->pluck('total', 'matricula_id')
            ->map(fn ($v): int => (int) $v)
            ->all();

        return $presencas;
    }
}
