<?php

declare(strict_types=1);

namespace App\Domain\Diario;

use DateTimeImmutable;

/**
 * Entidade: presença ou falta de uma matrícula em uma aula.
 */
final class RegistroFrequencia
{
    public function __construct(
        public readonly string $aulaId,
        public readonly int $matriculaId,
        public bool $presente,
        public int $versao,
        public DateTimeImmutable $alteradoEm,
    ) {}

    public static function novo(string $aulaId, int $matriculaId, bool $presente, DateTimeImmutable $em): self
    {
        return new self($aulaId, $matriculaId, $presente, 1, $em);
    }

    public function alterar(bool $presente, DateTimeImmutable $em): void
    {
        $this->presente = $presente;
        $this->versao++;
        $this->alteradoEm = $em;
    }
}
