<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

use App\Domain\Shared\Exceptions\DadosInvalidos;

/**
 * Objeto de valor: nota de 0,0 a 10,0 com até duas casas decimais.
 */
final readonly class Nota
{
    public const float MINIMA = 0.0;

    public const float MAXIMA = 10.0;

    public float $valor;

    public function __construct(float $valor)
    {
        if (is_nan($valor) || $valor < self::MINIMA || $valor > self::MAXIMA) {
            throw new DadosInvalidos('A nota deve estar entre 0 e 10.');
        }

        $this->valor = round($valor, 2);
    }

    public function igual(self $outra): bool
    {
        return abs($this->valor - $outra->valor) < 0.001;
    }
}
