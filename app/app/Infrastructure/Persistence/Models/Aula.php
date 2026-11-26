<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int $alocacao_id
 * @property Carbon $data
 * @property string $conteudo
 * @property string|null $observacoes
 * @property int $versao
 * @property Carbon $alterado_em
 * @property int $registrado_por
 * @property-read Alocacao $alocacao
 */
class Aula extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['id', 'alocacao_id', 'data', 'conteudo', 'observacoes', 'versao', 'alterado_em', 'registrado_por'];

    protected function casts(): array
    {
        return ['data' => 'date', 'alterado_em' => 'datetime', 'versao' => 'integer'];
    }

    /** @return BelongsTo<Alocacao, $this> */
    public function alocacao(): BelongsTo
    {
        return $this->belongsTo(Alocacao::class);
    }

    /** @return HasMany<Frequencia, $this> */
    public function frequencias(): HasMany
    {
        return $this->hasMany(Frequencia::class);
    }
}
