<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao\Contracts;

use App\Domain\Avaliacao\RegistroAuditoriaNota;

interface AuditoriaNotaRepository
{
    public function registrar(RegistroAuditoriaNota $registro): void;
}
