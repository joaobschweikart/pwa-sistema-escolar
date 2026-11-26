<?php

declare(strict_types=1);

namespace App\Infrastructure\Support;

use App\Domain\Shared\Contracts\GerenciadorTransacao;
use Illuminate\Support\Facades\DB;

final class TransacaoLaravel implements GerenciadorTransacao
{
    public function executar(callable $callback): mixed
    {
        return DB::transaction(fn () => $callback());
    }
}
