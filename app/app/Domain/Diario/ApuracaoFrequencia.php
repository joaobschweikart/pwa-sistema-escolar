<?php

declare(strict_types=1);

namespace App\Domain\Diario;

use App\Domain\Shared\Exceptions\DadosInvalidos;

/**
 * Regra de apuração de frequência: percentual de presenças sobre as aulas dadas,
 * com duas casas decimais.
 */
final class ApuracaoFrequencia
{
    public function percentual(int $presencas, int $totalAulas): ?float
    {
        if ($presencas < 0 || $totalAulas < 0 || $presencas > $totalAulas) {
            throw new DadosInvalidos('Quantidade de presenças inválida.');
        }

        if ($totalAulas === 0) {
            return null;
        }

        return round($presencas / $totalAulas * 100, 2);
    }

    public function faltas(int $presencas, int $totalAulas): int
    {
        return max(0, $totalAulas - $presencas);
    }
}
