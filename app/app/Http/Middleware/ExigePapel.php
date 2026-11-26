<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\Persistence\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe a rota a determinados papéis. Uso: ->middleware('papel:admin,gestor')
 */
final class ExigePapel
{
    public function handle(Request $request, Closure $next, string ...$papeis): Response
    {
        $usuario = $request->user();

        abort_unless($usuario instanceof User && in_array($usuario->papel->value, $papeis, true), 403, 'Acesso não permitido para o seu perfil.');

        return $next($request);
    }
}
