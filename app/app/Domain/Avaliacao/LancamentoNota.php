<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

use DateTimeImmutable;

/**
 * Entidade: nota de um aluno (matrícula) em uma avaliação, com controle de versão.
 */
final class LancamentoNota
{
    public function __construct(
        public readonly int $avaliacaoId,
        public readonly int $matriculaId,
        public Nota $valor,
        public int $versao,
        public DateTimeImmutable $alteradoEm,
        public int $lancadoPor,
        public ?int $id = null,
    ) {}

    public static function novo(int $avaliacaoId, int $matriculaId, Nota $valor, DateTimeImmutable $em, int $usuarioId): self
    {
        return new self($avaliacaoId, $matriculaId, $valor, 1, $em, $usuarioId);
    }

    public function alterar(Nota $valor, DateTimeImmutable $em, int $usuarioId): void
    {
        $this->valor = $valor;
        $this->versao++;
        $this->alteradoEm = $em;
        $this->lancadoPor = $usuarioId;
    }
}
