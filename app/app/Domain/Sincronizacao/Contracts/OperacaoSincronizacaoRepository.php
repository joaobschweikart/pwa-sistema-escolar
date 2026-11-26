<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao\Contracts;

use App\Domain\Sincronizacao\ResultadoOperacao;
use App\Domain\Sincronizacao\TipoOperacao;

/**
 * Registro das operações recebidas. O UUID gerado no cliente é chave única:
 * reenviar a mesma operação não produz um segundo efeito.
 */
interface OperacaoSincronizacaoRepository
{
    public function resultadoDe(string $uuid): ?ResultadoOperacao;

    /**
     * Reserva o UUID (status "processando"). Retorna false se ele já existe.
     */
    public function reservar(string $uuid, int $usuarioId, TipoOperacao $tipo): bool;

    public function concluir(string $uuid, ResultadoOperacao $resultado): void;
}
