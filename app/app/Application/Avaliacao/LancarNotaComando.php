<?php

declare(strict_types=1);

namespace App\Application\Avaliacao;

use App\Domain\Avaliacao\OrigemAlteracao;
use DateTimeImmutable;

final readonly class LancarNotaComando
{
    public function __construct(
        public int $avaliacaoId,
        public int $matriculaId,
        public float $valor,
        public int $versaoBase,
        public DateTimeImmutable $ocorridoEm,
        public OrigemAlteracao $origem,
        public ?string $operacaoUuid = null,
    ) {}
}
