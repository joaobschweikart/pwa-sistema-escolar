<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Acesso\Contracts\VinculoRepository;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\Avaliacao;
use App\Infrastructure\Persistence\Models\Matricula;

final class EloquentVinculoRepository implements VinculoRepository
{
    public function professorDaAlocacao(int $alocacaoId): ?int
    {
        $valor = Alocacao::query()->whereKey($alocacaoId)->value('professor_id');

        return $valor === null ? null : (int) $valor;
    }

    public function alocacaoDaAula(string $aulaId): ?int
    {
        $valor = Aula::query()->whereKey($aulaId)->value('alocacao_id');

        return $valor === null ? null : (int) $valor;
    }

    public function alocacaoDaAvaliacao(int $avaliacaoId): ?int
    {
        $valor = Avaliacao::query()->whereKey($avaliacaoId)->value('alocacao_id');

        return $valor === null ? null : (int) $valor;
    }

    public function escolaDaAlocacao(int $alocacaoId): ?int
    {
        $valor = Alocacao::query()
            ->join('turmas', 'turmas.id', '=', 'alocacoes.turma_id')
            ->where('alocacoes.id', $alocacaoId)
            ->value('turmas.escola_id');

        return $valor === null ? null : (int) $valor;
    }

    public function matriculaAtivaNaAlocacao(int $matriculaId, int $alocacaoId): bool
    {
        $turmaId = Alocacao::query()->whereKey($alocacaoId)->value('turma_id');

        return $turmaId !== null && Matricula::query()
            ->ativas()
            ->whereKey($matriculaId)
            ->where('turma_id', $turmaId)
            ->exists();
    }
}
