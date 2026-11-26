<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\AuditoriaNota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditoriaNota */
class AuditoriaNotaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ocorrido_em' => $this->ocorrido_em->toIso8601String(),
            'usuario' => $this->usuario->name,
            'avaliacao' => $this->avaliacao->titulo,
            'aluno' => $this->matricula->aluno->nome,
            'valor_anterior' => $this->valor_anterior,
            'valor_novo' => $this->valor_novo,
            'origem' => $this->origem,
            'operacao_uuid' => $this->operacao_uuid,
        ];
    }
}
