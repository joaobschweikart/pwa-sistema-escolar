<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vínculo professor ↔ turma/disciplina.
 *
 * @property int $id
 * @property int $turma_id
 * @property int $disciplina_id
 * @property int $professor_id
 * @property-read Turma $turma
 * @property-read Disciplina $disciplina
 * @property-read User $professor
 */
class Alocacao extends Model
{
    protected $table = 'alocacoes';

    protected $fillable = ['turma_id', 'disciplina_id', 'professor_id'];

    /** @return BelongsTo<Turma, $this> */
    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    /** @return BelongsTo<Disciplina, $this> */
    public function disciplina(): BelongsTo
    {
        return $this->belongsTo(Disciplina::class);
    }

    /** @return BelongsTo<User, $this> */
    public function professor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    /** @return HasMany<Aula, $this> */
    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }

    /** @return HasMany<Avaliacao, $this> */
    public function avaliacoes(): HasMany
    {
        return $this->hasMany(Avaliacao::class);
    }

    public function descricao(): string
    {
        return "{$this->turma->nome} · {$this->disciplina->nome}";
    }

    /**
     * @param  Builder<Alocacao>  $query
     * @return Builder<Alocacao>
     */
    public function scopeVisiveisPara(Builder $query, User $usuario): Builder
    {
        if ($usuario->ehAdmin()) {
            return $query;
        }

        if ($usuario->ehProfessor()) {
            return $query->where('professor_id', $usuario->id);
        }

        return $query->whereHas('turma', fn (Builder $q) => $q->where('escola_id', $usuario->escola_id));
    }
}
