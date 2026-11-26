<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marca a resposta como "app shell" do professor: somente respostas com este
 * cabeçalho são guardadas pelo service worker para abrir a PWA sem conexão.
 */
final class CabecalhoShellOffline
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-DEM-Shell', '1');
        $response->headers->set('Cache-Control', 'no-cache, private');

        return $response;
    }
}
