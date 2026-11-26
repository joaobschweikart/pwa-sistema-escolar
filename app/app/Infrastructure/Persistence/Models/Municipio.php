<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nome
 * @property string $uf
 */
class Municipio extends Model
{
    protected $fillable = ['nome', 'uf'];

    /** @return HasMany<Escola, $this> */
    public function escolas(): HasMany
    {
        return $this->hasMany(Escola::class);
    }
}
