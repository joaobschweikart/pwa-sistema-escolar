<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Papéis reconhecidos pelo sistema (capítulo 2 da modelagem).
 */
enum Papel: string
{
    case Admin = 'admin';
    case Gestor = 'gestor';
    case Professor = 'professor';

    public function rotulo(): string
    {
        return match ($this) {
            self::Admin => 'Admin da Secretaria',
            self::Gestor => 'Gestor Escolar',
            self::Professor => 'Professor',
        };
    }

    /** Somente o professor opera sem conexão. */
    public function operaOffline(): bool
    {
        return $this === self::Professor;
    }
}
