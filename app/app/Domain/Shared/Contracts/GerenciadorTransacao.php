<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contracts;

interface GerenciadorTransacao
{
    /**
     * Executa o callback dentro de uma transação atômica.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function executar(callable $callback): mixed;
}
