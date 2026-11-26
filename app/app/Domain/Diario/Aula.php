<?php

declare(strict_types=1);

namespace App\Domain\Diario;

use App\Domain\Shared\Exceptions\DadosInvalidos;
use DateTimeImmutable;

/**
 * Entidade: aula registrada no diário. O identificador é um UUID gerado no
 * cliente, o que permite criar a aula sem conexão sem colisão de chaves.
 */
final class Aula
{
    public function __construct(
        public readonly string $id,
        public readonly int $alocacaoId,
        public DateTimeImmutable $data,
        public string $conteudo,
        public ?string $observacoes,
        public int $versao,
        public DateTimeImmutable $alteradoEm,
        public int $registradoPor,
    ) {
        self::validarConteudo($conteudo);
    }

    public static function nova(
        string $id,
        int $alocacaoId,
        DateTimeImmutable $data,
        string $conteudo,
        ?string $observacoes,
        DateTimeImmutable $em,
        int $usuarioId,
    ): self {
        return new self($id, $alocacaoId, $data, $conteudo, $observacoes, 1, $em, $usuarioId);
    }

    public function alterar(DateTimeImmutable $data, string $conteudo, ?string $observacoes, DateTimeImmutable $em, int $usuarioId): void
    {
        self::validarConteudo($conteudo);
        $this->data = $data;
        $this->conteudo = $conteudo;
        $this->observacoes = $observacoes;
        $this->versao++;
        $this->alteradoEm = $em;
        $this->registradoPor = $usuarioId;
    }

    private static function validarConteudo(string $conteudo): void
    {
        if (trim($conteudo) === '') {
            throw new DadosInvalidos('O conteúdo da aula é obrigatório.');
        }
    }
}
