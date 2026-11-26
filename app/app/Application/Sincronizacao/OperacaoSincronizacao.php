<?php

declare(strict_types=1);

namespace App\Application\Sincronizacao;

use App\Application\Avaliacao\LancarNotaComando;
use App\Application\Diario\RegistrarAulaComando;
use App\Application\Diario\RegistrarFrequenciaComando;
use App\Domain\Sincronizacao\TipoOperacao;

/**
 * Operação de um lote de sincronização, já validada e convertida pelo app.http.
 * Quando $erro não é nulo a operação é rejeitada sem ser processada.
 */
final readonly class OperacaoSincronizacao
{
    public function __construct(
        public string $uuid,
        public ?TipoOperacao $tipo,
        public RegistrarAulaComando|RegistrarFrequenciaComando|LancarNotaComando|null $comando,
        public ?string $erro = null,
    ) {}

    public static function invalida(string $uuid, ?TipoOperacao $tipo, string $erro): self
    {
        return new self($uuid, $tipo, null, $erro);
    }
}
