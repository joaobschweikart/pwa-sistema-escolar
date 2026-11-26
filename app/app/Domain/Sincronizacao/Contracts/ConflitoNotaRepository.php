<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao\Contracts;

use App\Domain\Sincronizacao\ConflitoNota;
use App\Domain\Sincronizacao\ResolucaoConflito;
use DateTimeImmutable;

interface ConflitoNotaRepository
{
    public function registrar(ConflitoNota $conflito): int;

    public function buscarPendenteParaAtualizar(int $id): ?ConflitoNota;

    public function marcarResolvido(int $id, int $usuarioId, ResolucaoConflito $resolucao, DateTimeImmutable $em): void;
}
