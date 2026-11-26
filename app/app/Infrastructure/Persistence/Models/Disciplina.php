<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\DisciplinaFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nome
 * @property string $codigo
 * @property int|null $carga_horaria
 */
#[UseFactory(DisciplinaFactory::class)]
class Disciplina extends Model
{
    /** @use HasFactory<DisciplinaFactory> */
    use HasFactory;

    protected $fillable = ['nome', 'codigo', 'carga_horaria'];

    /** @return HasMany<Alocacao, $this> */
    public function alocacoes(): HasMany
    {
        return $this->hasMany(Alocacao::class);
    }
}
