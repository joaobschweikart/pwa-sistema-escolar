<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Diario\Aula;
use App\Domain\Diario\Contracts\AulaRepository;
use App\Infrastructure\Persistence\Models\Aula as AulaModel;

final class EloquentAulaRepository implements AulaRepository
{
    public function buscarParaAtualizar(string $id): ?Aula
    {
        $m = AulaModel::query()->lockForUpdate()->find($id);

        return $m === null ? null : new Aula(
            id: $m->id,
            alocacaoId: $m->alocacao_id,
            data: $m->data->toDateTimeImmutable(),
            conteudo: $m->conteudo,
            observacoes: $m->observacoes,
            versao: $m->versao,
            alteradoEm: $m->alterado_em->toDateTimeImmutable(),
            registradoPor: $m->registrado_por,
        );
    }

    public function salvar(Aula $aula): void
    {
        AulaModel::query()->updateOrCreate(['id' => $aula->id], [
            'alocacao_id' => $aula->alocacaoId,
            'data' => $aula->data->format('Y-m-d'),
            'conteudo' => $aula->conteudo,
            'observacoes' => $aula->observacoes,
            'versao' => $aula->versao,
            'alterado_em' => $aula->alteradoEm,
            'registrado_por' => $aula->registradoPor,
        ]);
    }
}
