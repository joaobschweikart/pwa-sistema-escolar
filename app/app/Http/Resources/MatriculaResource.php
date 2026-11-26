<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Matricula */
class MatriculaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'matricula_id' => $this->id,
            'aluno_id' => $this->aluno_id,
            'nome' => $this->aluno->nome,
            'codigo' => $this->aluno->codigo,
            'numero_chamada' => $this->numero_chamada,
            'situacao' => $this->situacao,
        ];
    }
}
