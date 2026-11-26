<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Diario\Contracts\FrequenciaRepository;
use App\Domain\Diario\RegistroFrequencia;
use App\Infrastructure\Persistence\Models\Frequencia;

final class EloquentFrequenciaRepository implements FrequenciaRepository
{
    public function buscarParaAtualizar(string $aulaId, int $matriculaId): ?RegistroFrequencia
    {
        $m = Frequencia::query()->where('aula_id', $aulaId)->where('matricula_id', $matriculaId)->lockForUpdate()->first();

        return $m === null ? null : new RegistroFrequencia(
            $m->aula_id,
            $m->matricula_id,
            $m->presente,
            $m->versao,
            $m->alterado_em->toDateTimeImmutable(),
        );
    }

    public function salvar(RegistroFrequencia $registro): void
    {
        Frequencia::query()->updateOrCreate(
            ['aula_id' => $registro->aulaId, 'matricula_id' => $registro->matriculaId],
            ['presente' => $registro->presente, 'versao' => $registro->versao, 'alterado_em' => $registro->alteradoEm],
        );
    }
}
