<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\Turma;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Turma */
class TurmaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'serie' => $this->serie,
            'turno' => $this->turno,
            'ano_letivo' => $this->ano_letivo,
            'escola' => $this->whenLoaded('escola', fn () => ['id' => $this->escola->id, 'nome' => $this->escola->nome]),
            'matriculas_count' => $this->whenCounted('matriculas'),
        ];
    }
}
