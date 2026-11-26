<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\Frequencia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Aula */
class AulaResource extends JsonResource
{
    /** O recurso tem um campo "data" (data da aula/avaliação): força o envelope {"data": ...}. */
    public static bool $forceWrapping = true;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alocacao_id' => $this->alocacao_id,
            'data' => $this->data->toDateString(),
            'conteudo' => $this->conteudo,
            'observacoes' => $this->observacoes,
            'versao' => $this->versao,
            'alterado_em' => $this->alterado_em->toIso8601ZuluString('millisecond'),
            'frequencias' => $this->whenLoaded('frequencias', fn () => $this->frequencias->map(fn (Frequencia $f) => [
                'matricula_id' => $f->matricula_id,
                'presente' => $f->presente,
                'versao' => $f->versao,
            ])->values()),
        ];
    }
}
