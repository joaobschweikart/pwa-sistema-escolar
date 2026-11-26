<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao\Contracts;

/**
 * Fornece os dados brutos para o boletim de uma alocação (turma + disciplina).
 */
interface DadosBoletimRepository
{
    /**
     * @return list<array{matricula_id: int, aluno_id: int, aluno: string, numero_chamada: int|null}>
     */
    public function matriculasAtivas(int $alocacaoId): array;

    /**
     * @return list<array{matricula_id: int, valor: float, peso: float}>
     */
    public function notasComPeso(int $alocacaoId, ?int $periodo = null): array;

    public function totalAulas(int $alocacaoId): int;

    /**
     * @return array<int, int> presenças indexadas por matricula_id
     */
    public function presencasPorMatricula(int $alocacaoId): array;
}
