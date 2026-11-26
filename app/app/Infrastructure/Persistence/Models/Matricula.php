<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $aluno_id
 * @property int $turma_id
 * @property int|null $numero_chamada
 * @property string $situacao
 * @property Carbon $data_matricula
 * @property-read Aluno $aluno
 * @property-read Turma $turma
 */
class Matricula extends Model
{
    public const array SITUACOES = ['ativa' => 'Ativa', 'transferida' => 'Transferida', 'cancelada' => 'Cancelada'];

    protected $fillable = ['aluno_id', 'turma_id', 'numero_chamada', 'situacao', 'data_matricula'];

    protected function casts(): array
    {
        return ['data_matricula' => 'date', 'numero_chamada' => 'integer'];
    }

    /** @return BelongsTo<Aluno, $this> */
    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    /** @return BelongsTo<Turma, $this> */
    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    /**
     * @param  Builder<Matricula>  $query
     * @return Builder<Matricula>
     */
    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where('situacao', 'ativa');
    }
}
