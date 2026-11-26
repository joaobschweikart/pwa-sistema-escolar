<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class AutenticacaoController extends Controller
{
    public function formulario(): View
    {
        return view('auth.login');
    }

    public function entrar(Request $request): RedirectResponse
    {
        $credenciais = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credenciais + ['ativo' => true], $request->boolean('lembrar'))) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('painel'));
    }

    public function sair(Request $request): RedirectResponse
    {
        // Revoga o token da PWA emitido nesta sessão (o JS também limpa cache e IndexedDB)
        $tokenId = $request->session()->get('pwa_token_id');
        if ($tokenId !== null) {
            PersonalAccessToken::query()->whereKey($tokenId)->delete();
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Você saiu do sistema.');
    }

    /**
     * Emite o token Bearer usado pela PWA do professor (API e sincronização offline).
     */
    public function tokenPwa(Request $request): JsonResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();
        abort_unless($usuario->ehProfessor(), 403);

        $anterior = $request->session()->get('pwa_token_id');
        if ($anterior !== null) {
            PersonalAccessToken::query()->whereKey($anterior)->where('tokenable_id', $usuario->id)->delete();
        }

        $expira = now()->addDays((int) config('dem.pwa_token_dias'));
        $token = $usuario->createToken('pwa', ['*'], $expira);
        $request->session()->put('pwa_token_id', $token->accessToken->getKey());

        return response()->json([
            'token' => $token->plainTextToken,
            'expira_em' => $expira->toIso8601String(),
            'usuario' => ['id' => $usuario->id, 'nome' => $usuario->name],
        ]);
    }
}
