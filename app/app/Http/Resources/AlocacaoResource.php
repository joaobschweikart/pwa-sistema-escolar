<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\Alocacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Alocacao */
class AlocacaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'turma' => [
                'id' => $this->turma->id,
                'nome' => $this->turma->nome,
                'turno' => $this->turma->turno,
                'ano_letivo' => $this->turma->ano_letivo,
                'escola' => $this->turma->escola->nome,
            ],
            'disciplina' => ['id' => $this->disciplina->id, 'nome' => $this->disciplina->nome],
            'professor' => ['id' => $this->professor->id, 'nome' => $this->professor->name],
        ];
    }
}
