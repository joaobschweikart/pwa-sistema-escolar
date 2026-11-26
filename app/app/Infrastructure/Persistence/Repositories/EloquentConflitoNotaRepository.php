<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Sincronizacao\ConflitoNota;
use App\Domain\Sincronizacao\Contracts\ConflitoNotaRepository;
use App\Domain\Sincronizacao\ResolucaoConflito;
use App\Infrastructure\Persistence\Models\ConflitoNota as ConflitoModel;
use DateTimeImmutable;

final class EloquentConflitoNotaRepository implements ConflitoNotaRepository
{
    public function registrar(ConflitoNota $conflito): int
    {
        return ConflitoModel::query()->create([
            'operacao_uuid' => $conflito->operacaoUuid,
            'avaliacao_id' => $conflito->avaliacaoId,
            'matricula_id' => $conflito->matriculaId,
            'professor_id' => $conflito->professorId,
            'valor_servidor' => $conflito->valorServidor,
            'valor_proposto' => $conflito->valorProposto,
            'versao_base' => $conflito->versaoBase,
            'versao_servidor' => $conflito->versaoServidor,
            'proposto_em' => $conflito->propostoEm,
            'status' => 'pendente',
        ])->id;
    }

    public function buscarPendenteParaAtualizar(int $id): ?ConflitoNota
    {
        $m = ConflitoModel::query()->whereKey($id)->where('status', 'pendente')->lockForUpdate()->first();

        return $m === null ? null : new ConflitoNota(
            operacaoUuid: $m->operacao_uuid,
            avaliacaoId: $m->avaliacao_id,
            matriculaId: $m->matricula_id,
            professorId: $m->professor_id,
            valorServidor: $m->valor_servidor,
            valorProposto: $m->valor_proposto,
            versaoBase: $m->versao_base,
            versaoServidor: $m->versao_servidor,
            propostoEm: $m->proposto_em->toDateTimeImmutable(),
            id: $m->id,
        );
    }

    public function marcarResolvido(int $id, int $usuarioId, ResolucaoConflito $resolucao, DateTimeImmutable $em): void
    {
        ConflitoModel::query()->whereKey($id)->update([
            'status' => 'resolvido',
            'resolucao' => $resolucao->value,
            'resolvido_por' => $usuarioId,
            'resolvido_em' => $em,
        ]);
    }
}
