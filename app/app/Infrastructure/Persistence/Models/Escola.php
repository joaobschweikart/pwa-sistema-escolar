<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\EscolaFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $municipio_id
 * @property string $nome
 * @property string|null $codigo_inep
 * @property string|null $endereco
 * @property string|null $telefone
 * @property-read Municipio $municipio
 */
#[UseFactory(EscolaFactory::class)]
class Escola extends Model
{
    /** @use HasFactory<EscolaFactory> */
    use HasFactory;

    protected $fillable = ['municipio_id', 'nome', 'codigo_inep', 'endereco', 'telefone'];

    /** @return BelongsTo<Municipio, $this> */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    /** @return HasMany<Turma, $this> */
    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }

    /** @return HasMany<Aluno, $this> */
    public function alunos(): HasMany
    {
        return $this->hasMany(Aluno::class);
    }

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
