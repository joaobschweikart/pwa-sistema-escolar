<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

/**
 * Regra de cálculo de média: média ponderada pelos pesos das avaliações,
 * arredondada para uma casa decimal (meio para cima).
 * Com todos os pesos iguais a 1, equivale à média aritmética simples.
 */
final class CalculadoraMedia
{
    /**
     * @param  list<NotaPonderada>  $notas
     */
    public function calcular(array $notas): ?float
    {
        if ($notas === []) {
            return null;
        }

        $somaPonderada = 0.0;
        $somaPesos = 0.0;

        foreach ($notas as $item) {
            $somaPonderada += $item->nota->valor * $item->peso;
            $somaPesos += $item->peso;
        }

        return round($somaPonderada / $somaPesos, 1, PHP_ROUND_HALF_UP);
    }
}
