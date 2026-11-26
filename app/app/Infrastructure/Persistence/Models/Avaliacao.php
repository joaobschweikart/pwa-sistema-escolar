<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $alocacao_id
 * @property string $titulo
 * @property string|null $descricao
 * @property Carbon $data
 * @property int $periodo
 * @property float $peso
 * @property-read Alocacao $alocacao
 */
class Avaliacao extends Model
{
    protected $table = 'avaliacoes';

    protected $fillable = ['alocacao_id', 'titulo', 'descricao', 'data', 'periodo', 'peso'];

    protected function casts(): array
    {
        return ['data' => 'date', 'periodo' => 'integer', 'peso' => 'float'];
    }

    /** @return BelongsTo<Alocacao, $this> */
    public function alocacao(): BelongsTo
    {
        return $this->belongsTo(Alocacao::class);
    }

    /** @return HasMany<Nota, $this> */
    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class);
    }
}
