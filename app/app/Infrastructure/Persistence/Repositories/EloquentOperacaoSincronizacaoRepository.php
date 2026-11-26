<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Sincronizacao\Contracts\OperacaoSincronizacaoRepository;
use App\Domain\Sincronizacao\ResultadoOperacao;
use App\Domain\Sincronizacao\StatusOperacao;
use App\Domain\Sincronizacao\TipoOperacao;
use App\Infrastructure\Persistence\Models\OperacaoSincronizacao;
use Illuminate\Database\UniqueConstraintViolationException;

final class EloquentOperacaoSincronizacaoRepository implements OperacaoSincronizacaoRepository
{
    public function resultadoDe(string $uuid): ?ResultadoOperacao
    {
        $m = OperacaoSincronizacao::query()->find($uuid);

        if ($m === null) {
            return null;
        }

        if ($m->resultado === null) {
            return new ResultadoOperacao(StatusOperacao::Processando, 'Operação em processamento.');
        }

        return ResultadoOperacao::deArray($m->resultado);
    }

    public function reservar(string $uuid, int $usuarioId, TipoOperacao $tipo): bool
    {
        try {
            OperacaoSincronizacao::query()->create([
                'uuid' => $uuid,
                'usuario_id' => $usuarioId,
                'tipo' => $tipo->value,
                'status' => StatusOperacao::Processando->value,
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    public function concluir(string $uuid, ResultadoOperacao $resultado): void
    {
        OperacaoSincronizacao::query()->whereKey($uuid)->update([
            'status' => $resultado->status->value,
            'resultado' => json_encode($resultado->paraArray(), JSON_THROW_ON_ERROR),
        ]);
    }
}
