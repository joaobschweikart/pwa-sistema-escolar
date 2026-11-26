<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

use DateTimeImmutable;

/**
 * Registro imutável de auditoria: quem alterou a nota, quando e de/para qual valor.
 */
final readonly class RegistroAuditoriaNota
{
    public function __construct(
        public ?int $notaId,
        public int $avaliacaoId,
        public int $matriculaId,
        public int $usuarioId,
        public ?float $valorAnterior,
        public float $valorNovo,
        public OrigemAlteracao $origem,
        public DateTimeImmutable $ocorridoEm,
        public ?string $operacaoUuid = null,
    ) {}
}
