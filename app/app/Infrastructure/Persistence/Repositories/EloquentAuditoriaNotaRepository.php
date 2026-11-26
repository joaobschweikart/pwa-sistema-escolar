<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Avaliacao\Contracts\AuditoriaNotaRepository;
use App\Domain\Avaliacao\RegistroAuditoriaNota;
use App\Infrastructure\Persistence\Models\AuditoriaNota;

final class EloquentAuditoriaNotaRepository implements AuditoriaNotaRepository
{
    public function registrar(RegistroAuditoriaNota $registro): void
    {
        AuditoriaNota::query()->create([
            'nota_id' => $registro->notaId,
            'avaliacao_id' => $registro->avaliacaoId,
            'matricula_id' => $registro->matriculaId,
            'usuario_id' => $registro->usuarioId,
            'valor_anterior' => $registro->valorAnterior,
            'valor_novo' => $registro->valorNovo,
            'origem' => $registro->origem->value,
            'operacao_uuid' => $registro->operacaoUuid,
            'ocorrido_em' => $registro->ocorridoEm,
        ]);
    }
}
