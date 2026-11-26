<?php

declare(strict_types=1);

namespace App\Domain\Acesso\Contracts;

/**
 * Consulta os vínculos professor ↔ turma/disciplina (alocações) e matrículas.
 */
interface VinculoRepository
{
    public function professorDaAlocacao(int $alocacaoId): ?int;

    public function alocacaoDaAula(string $aulaId): ?int;

    public function alocacaoDaAvaliacao(int $avaliacaoId): ?int;

    public function escolaDaAlocacao(int $alocacaoId): ?int;

    /** A matrícula está ativa na turma da alocação? */
    public function matriculaAtivaNaAlocacao(int $matriculaId, int $alocacaoId): bool;
}
