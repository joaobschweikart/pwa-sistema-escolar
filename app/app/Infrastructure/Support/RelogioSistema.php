<?php

declare(strict_types=1);

namespace App\Infrastructure\Support;

use App\Domain\Shared\Contracts\Relogio;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

final class RelogioSistema implements Relogio
{
    public function agora(): DateTimeImmutable
    {
        return Carbon::now()->toDateTimeImmutable();
    }
}
