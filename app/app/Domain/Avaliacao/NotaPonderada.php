<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

use App\Domain\Shared\Exceptions\DadosInvalidos;

final readonly class NotaPonderada
{
    public function __construct(
        public Nota $nota,
        public float $peso = 1.0,
    ) {
        if ($peso <= 0) {
            throw new DadosInvalidos('O peso da avaliação deve ser maior que zero.');
        }
    }
}
