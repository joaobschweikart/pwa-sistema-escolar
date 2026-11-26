<?php

use App\Domain\Shared\Exceptions\AcessoNegado;
use App\Domain\Shared\Exceptions\DadosInvalidos;
use App\Domain\Shared\Exceptions\RegistroNaoEncontrado;
use App\Http\Middleware\CabecalhoShellOffline;
use App\Http\Middleware\ExigePapel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: ['172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16', '127.0.0.1']);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('painel'));
        $middleware->alias(['shell.offline' => CabecalhoShellOffline::class, 'papel' => ExigePapel::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Exceções do domínio -> respostas HTTP
        $json = fn (int $status) => fn (Throwable $e, Request $request) => $request->is('api/*') || $request->expectsJson()
            ? response()->json(['message' => $e->getMessage()], $status)
            : null;
        $exceptions->render(fn (AcessoNegado $e, Request $r) => $json(403)($e, $r) ?? abort(403, $e->getMessage()));
        $exceptions->render(fn (RegistroNaoEncontrado $e, Request $r) => $json(404)($e, $r) ?? abort(404, $e->getMessage()));
        $exceptions->render(fn (DadosInvalidos $e, Request $r) => $json(422)($e, $r) ?? back()->withErrors(['geral' => $e->getMessage()])->withInput());

        // Sessão expirada em página aberta offline por muito tempo: volta ao login
        $exceptions->render(fn (TokenMismatchException $e, Request $r) => $r->is('logout')
            ? redirect()->route('login')->with('status', 'Sessão encerrada.')
            : null);
    })->create();
