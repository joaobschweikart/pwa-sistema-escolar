<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\AlunoFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $escola_id
 * @property string $nome
 * @property string $codigo
 * @property Carbon $data_nascimento
 * @property string|null $responsavel
 * @property-read Escola $escola
 */
#[UseFactory(AlunoFactory::class)]
class Aluno extends Model
{
    /** @use HasFactory<AlunoFactory> */
    use HasFactory;

    protected $fillable = ['escola_id', 'nome', 'codigo', 'data_nascimento', 'responsavel'];

    protected function casts(): array
    {
        return ['data_nascimento' => 'date'];
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

    /**
     * @param  Builder<Aluno>  $query
     * @return Builder<Aluno>
     */
    public function scopeVisiveisPara(Builder $query, User $usuario): Builder
    {
        return $usuario->ehAdmin() ? $query : $query->where('escola_id', $usuario->escola_id);
    }
}
