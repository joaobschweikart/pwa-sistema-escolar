<?php

declare(strict_types=1);

namespace App\Domain\Diario\Contracts;

use App\Domain\Diario\Aula;

interface AulaRepository
{
    public function buscarParaAtualizar(string $id): ?Aula;

    public function salvar(Aula $aula): void;
}
