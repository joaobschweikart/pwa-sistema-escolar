<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as RegraSenha;
use Illuminate\View\View;

/**
 * Recuperação de senha por e-mail (log em dev, Amazon SES em produção).
 */
class SenhaController extends Controller
{
    public function solicitar(): View
    {
        return view('auth.esqueci-senha');
    }

    public function enviarLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Resposta neutra: não revela se o e-mail existe
        return back()->with('status', 'Se o e-mail estiver cadastrado, você receberá um link para redefinir a senha.');
    }

    public function formulario(Request $request, string $token): View
    {
        return view('auth.redefinir-senha', ['token' => $token, 'email' => $request->string('email')->toString()]);
    }

    public function redefinir(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', RegraSenha::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $usuario, string $senha) {
                $usuario->forceFill(['password' => $senha, 'remember_token' => Str::random(60)])->save();
                $usuario->tokens()->delete();
                event(new PasswordReset($usuario));
            },
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
