<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $operacao_uuid
 * @property int $avaliacao_id
 * @property int $matricula_id
 * @property int $professor_id
 * @property float $valor_servidor
 * @property float $valor_proposto
 * @property int $versao_base
 * @property int $versao_servidor
 * @property Carbon $proposto_em
 * @property string $status
 * @property string|null $resolucao
 * @property int|null $resolvido_por
 * @property Carbon|null $resolvido_em
 * @property Carbon $created_at
 * @property-read Avaliacao $avaliacao
 * @property-read Matricula $matricula
 * @property-read User $professor
 * @property-read User|null $resolvidoPor
 */
class ConflitoNota extends Model
{
    protected $table = 'conflitos_nota';

    protected $fillable = [
        'operacao_uuid', 'avaliacao_id', 'matricula_id', 'professor_id', 'valor_servidor', 'valor_proposto',
        'versao_base', 'versao_servidor', 'proposto_em', 'status', 'resolucao', 'resolvido_por', 'resolvido_em',
    ];

    protected function casts(): array
    {
        return [
            'valor_servidor' => 'float',
            'valor_proposto' => 'float',
            'versao_base' => 'integer',
            'versao_servidor' => 'integer',
            'proposto_em' => 'datetime',
            'resolvido_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<Avaliacao, $this> */
    public function avaliacao(): BelongsTo
    {
        return $this->belongsTo(Avaliacao::class);
    }

    /** @return BelongsTo<Matricula, $this> */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /** @return BelongsTo<User, $this> */
    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolvido_por');
    }

    /**
     * @param  Builder<ConflitoNota>  $query
     * @return Builder<ConflitoNota>
     */
    public function scopeVisiveisPara(Builder $query, User $usuario): Builder
    {
        if ($usuario->ehAdmin()) {
            return $query;
        }

        return $query->whereHas('avaliacao.alocacao.turma', fn (Builder $q) => $q->where('escola_id', $usuario->escola_id));
    }
}
