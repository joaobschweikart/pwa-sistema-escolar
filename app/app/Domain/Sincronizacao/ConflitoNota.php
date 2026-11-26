<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

use DateTimeImmutable;

/**
 * Conflito de nota aguardando decisão humana do gestor (efeito acadêmico).
 */
final readonly class ConflitoNota
{
    public function __construct(
        public string $operacaoUuid,
        public int $avaliacaoId,
        public int $matriculaId,
        public int $professorId,
        public float $valorServidor,
        public float $valorProposto,
        public int $versaoBase,
        public int $versaoServidor,
        public DateTimeImmutable $propostoEm,
        public ?int $id = null,
    ) {}
}
