<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

final readonly class ResultadoOperacao
{
    /**
     * @param  array<string, mixed>  $dados
     */
    public function __construct(
        public StatusOperacao $status,
        public string $mensagem,
        public array $dados = [],
    ) {}

    /** @param array<string, mixed> $dados */
    public static function aplicada(string $mensagem, array $dados = []): self
    {
        return new self(StatusOperacao::Aplicada, $mensagem, $dados);
    }

    /** @param array<string, mixed> $dados */
    public static function descartada(string $mensagem, array $dados = []): self
    {
        return new self(StatusOperacao::Descartada, $mensagem, $dados);
    }

    /** @param array<string, mixed> $dados */
    public static function conflito(string $mensagem, array $dados = []): self
    {
        return new self(StatusOperacao::Conflito, $mensagem, $dados);
    }

    /** @param array<string, mixed> $dados */
    public static function rejeitada(string $mensagem, array $dados = []): self
    {
        return new self(StatusOperacao::Rejeitada, $mensagem, $dados);
    }

    /** @return array{status: string, mensagem: string, dados: array<string, mixed>} */
    public function paraArray(): array
    {
        return ['status' => $this->status->value, 'mensagem' => $this->mensagem, 'dados' => $this->dados];
    }

    /** @param array{status: string, mensagem: string, dados?: array<string, mixed>} $dados */
    public static function deArray(array $dados): self
    {
        return new self(StatusOperacao::from($dados['status']), $dados['mensagem'], $dados['dados'] ?? []);
    }
}
