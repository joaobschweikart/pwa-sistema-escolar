<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Application\Shared\ContextoUsuario;
use App\Infrastructure\Persistence\Models\User;

final class Contexto
{
    public static function de(User $usuario): ContextoUsuario
    {
        return new ContextoUsuario($usuario->id, $usuario->papel, $usuario->escola_id);
    }
}
