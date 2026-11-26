<?php

declare(strict_types=1);

namespace App\Application\Diario;

use DateTimeImmutable;

final readonly class RegistrarAulaComando
{
    public function __construct(
        public string $aulaId,
        public int $alocacaoId,
        public DateTimeImmutable $data,
        public string $conteudo,
        public ?string $observacoes,
        public int $versaoBase,
        public DateTimeImmutable $ocorridoEm,
    ) {}
}
