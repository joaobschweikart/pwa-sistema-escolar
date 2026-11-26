<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\ConflitoNota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConflitoNota */
class ConflitoNotaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'avaliacao' => ['id' => $this->avaliacao->id, 'titulo' => $this->avaliacao->titulo],
            'turma' => $this->avaliacao->alocacao->turma->nome,
            'disciplina' => $this->avaliacao->alocacao->disciplina->nome,
            'aluno' => ['matricula_id' => $this->matricula_id, 'nome' => $this->matricula->aluno->nome],
            'professor' => $this->professor->name,
            'valor_servidor' => $this->valor_servidor,
            'valor_proposto' => $this->valor_proposto,
            'versao_base' => $this->versao_base,
            'versao_servidor' => $this->versao_servidor,
            'proposto_em' => $this->proposto_em->toIso8601String(),
            'resolucao' => $this->resolucao,
            'resolvido_por' => $this->resolvidoPor?->name,
            'resolvido_em' => $this->resolvido_em?->toIso8601String(),
        ];
    }
}
