<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $avaliacao_id
 * @property int $matricula_id
 * @property float $valor
 * @property int $versao
 * @property Carbon $alterado_em
 * @property int $lancado_por
 * @property-read Avaliacao $avaliacao
 * @property-read Matricula $matricula
 */
class Nota extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['avaliacao_id', 'matricula_id', 'valor', 'versao', 'alterado_em', 'lancado_por'];

    protected function casts(): array
    {
        return ['valor' => 'float', 'alterado_em' => 'datetime', 'versao' => 'integer'];
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
}
