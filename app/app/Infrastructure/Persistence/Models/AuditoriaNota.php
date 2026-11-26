<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Registro de auditoria imutável (somente inserção).
 *
 * @property int $id
 * @property int|null $nota_id
 * @property int $avaliacao_id
 * @property int $matricula_id
 * @property int $usuario_id
 * @property float|null $valor_anterior
 * @property float $valor_novo
 * @property string $origem
 * @property string|null $operacao_uuid
 * @property Carbon $ocorrido_em
 * @property-read User $usuario
 * @property-read Avaliacao $avaliacao
 * @property-read Matricula $matricula
 */
class AuditoriaNota extends Model
{
    public $timestamps = false;

    protected $table = 'auditoria_notas';

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['nota_id', 'avaliacao_id', 'matricula_id', 'usuario_id', 'valor_anterior', 'valor_novo', 'origem', 'operacao_uuid', 'ocorrido_em'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Registros de auditoria são imutáveis.'));
        static::deleting(fn () => throw new LogicException('Registros de auditoria são imutáveis.'));
    }

    protected function casts(): array
    {
        return ['valor_anterior' => 'float', 'valor_novo' => 'float', 'ocorrido_em' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
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
