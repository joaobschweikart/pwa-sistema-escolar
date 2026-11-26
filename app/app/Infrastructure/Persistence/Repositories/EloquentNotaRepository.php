<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Avaliacao\Contracts\NotaRepository;
use App\Domain\Avaliacao\LancamentoNota;
use App\Domain\Avaliacao\Nota as NotaValor;
use App\Infrastructure\Persistence\Models\Nota;

final class EloquentNotaRepository implements NotaRepository
{
    public function buscarParaAtualizar(int $avaliacaoId, int $matriculaId): ?LancamentoNota
    {
        $m = Nota::query()->where('avaliacao_id', $avaliacaoId)->where('matricula_id', $matriculaId)->lockForUpdate()->first();

        return $m === null ? null : new LancamentoNota(
            avaliacaoId: $m->avaliacao_id,
            matriculaId: $m->matricula_id,
            valor: new NotaValor($m->valor),
            versao: $m->versao,
            alteradoEm: $m->alterado_em->toDateTimeImmutable(),
            lancadoPor: $m->lancado_por,
            id: $m->id,
        );
    }

    public function salvar(LancamentoNota $nota): LancamentoNota
    {
        $m = Nota::query()->updateOrCreate(
            ['avaliacao_id' => $nota->avaliacaoId, 'matricula_id' => $nota->matriculaId],
            [
                'valor' => $nota->valor->valor,
                'versao' => $nota->versao,
                'alterado_em' => $nota->alteradoEm,
                'lancado_por' => $nota->lancadoPor,
            ],
        );

        $nota->id = $m->id;

        return $nota;
    }
}
