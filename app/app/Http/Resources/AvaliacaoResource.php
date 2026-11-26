<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\Avaliacao;
use App\Infrastructure\Persistence\Models\Nota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Avaliacao */
class AvaliacaoResource extends JsonResource
{
    /** O recurso tem um campo "data" (data da aula/avaliação): força o envelope {"data": ...}. */
    public static bool $forceWrapping = true;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alocacao_id' => $this->alocacao_id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'data' => $this->data->toDateString(),
            'periodo' => $this->periodo,
            'peso' => $this->peso,
            'notas' => $this->whenLoaded('notas', fn () => $this->notas->map(fn (Nota $n) => [
                'matricula_id' => $n->matricula_id,
                'valor' => $n->valor,
                'versao' => $n->versao,
            ])->values()),
        ];
    }
}
