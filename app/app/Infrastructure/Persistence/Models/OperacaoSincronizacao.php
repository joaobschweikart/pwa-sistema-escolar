<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $uuid
 * @property int $usuario_id
 * @property string $tipo
 * @property string $status
 * @property array{status: string, mensagem: string, dados?: array<string, mixed>}|null $resultado
 */
class OperacaoSincronizacao extends Model
{
    public $incrementing = false;

    protected $table = 'sync_operacoes';

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $fillable = ['uuid', 'usuario_id', 'tipo', 'status', 'resultado'];

    protected function casts(): array
    {
        return ['resultado' => 'array'];
    }
}
