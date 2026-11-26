<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $aula_id
 * @property int $matricula_id
 * @property bool $presente
 * @property int $versao
 * @property Carbon $alterado_em
 * @property-read Aula $aula
 * @property-read Matricula $matricula
 */
class Frequencia extends Model
{
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['aula_id', 'matricula_id', 'presente', 'versao', 'alterado_em'];

    protected function casts(): array
    {
        return ['presente' => 'boolean', 'alterado_em' => 'datetime', 'versao' => 'integer'];
    }

    /** @return BelongsTo<Aula, $this> */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    /** @return BelongsTo<Matricula, $this> */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }
}
