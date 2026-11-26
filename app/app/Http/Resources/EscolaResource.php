<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\Escola;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Escola */
class EscolaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'codigo_inep' => $this->codigo_inep,
            'endereco' => $this->endereco,
            'telefone' => $this->telefone,
            'municipio' => $this->whenLoaded('municipio', fn () => ['id' => $this->municipio->id, 'nome' => $this->municipio->nome, 'uf' => $this->municipio->uf]),
            'turmas_count' => $this->whenCounted('turmas'),
        ];
    }
}
