<?php

declare(strict_types=1);

namespace App\Domain\Diario\Contracts;

use App\Domain\Diario\RegistroFrequencia;

interface FrequenciaRepository
{
    public function buscarParaAtualizar(string $aulaId, int $matriculaId): ?RegistroFrequencia;

    public function salvar(RegistroFrequencia $registro): void;
}
