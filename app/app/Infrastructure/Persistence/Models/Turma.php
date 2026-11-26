<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\TurmaFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $escola_id
 * @property string $nome
 * @property string $serie
 * @property string $turno
 * @property int $ano_letivo
 * @property-read Escola $escola
 */
#[UseFactory(TurmaFactory::class)]
class Turma extends Model
{
    /** @use HasFactory<TurmaFactory> */
    use HasFactory;

    public const array TURNOS = ['matutino' => 'Matutino', 'vespertino' => 'Vespertino', 'noturno' => 'Noturno', 'integral' => 'Integral'];

    protected $fillable = ['escola_id', 'nome', 'serie', 'turno', 'ano_letivo'];

    protected function casts(): array
    {
        return ['ano_letivo' => 'integer'];
    }

    /** @return BelongsTo<Escola, $this> */
    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class);
    }

    /** @return HasMany<Matricula, $this> */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    /** @return HasMany<Alocacao, $this> */
    public function alocacoes(): HasMany
    {
        return $this->hasMany(Alocacao::class);
    }

    /**
     * Turmas que o usuário pode ver: admin todas; gestor as da escola; professor as vinculadas.
     *
     * @param  Builder<Turma>  $query
     * @return Builder<Turma>
     */
    public function scopeVisiveisPara(Builder $query, User $usuario): Builder
    {
        if ($usuario->ehAdmin()) {
            return $query;
        }

        if ($usuario->ehProfessor()) {
            return $query->whereHas('alocacoes', fn (Builder $q) => $q->where('professor_id', $usuario->id));
        }

        return $query->where('escola_id', $usuario->escola_id);
    }
}
