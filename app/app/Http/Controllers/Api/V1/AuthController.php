<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmitirTokenRequest;
use App\Http\Resources\UserResource;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @tags Autenticação
 */
class AuthController extends Controller
{
    /**
     * Emitir token
     *
     * Autentica com e-mail e senha e devolve um token Bearer (Laravel Sanctum).
     *
     * @unauthenticated
     */
    public function emitir(EmitirTokenRequest $request): JsonResponse
    {
        $usuario = User::query()->where('email', $request->string('email'))->first();

        if ($usuario === null || ! $usuario->ativo || ! Hash::check($request->string('password')->toString(), $usuario->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }

        $expira = now()->addDays((int) config('dem.pwa_token_dias'));
        $token = $usuario->createToken($request->string('dispositivo')->toString(), ['*'], $expira);

        return response()->json([
            'token' => $token->plainTextToken,
            'tipo' => 'Bearer',
            'expira_em' => $expira->toIso8601String(),
            'usuario' => new UserResource($usuario->load('escola')),
        ], 201);
    }

    /**
     * Revogar token
     *
     * Revoga o token usado na requisição (logout do dispositivo).
     */
    public function revogar(Request $request): Response
    {
        /** @var User $usuario */
        $usuario = $request->user();
        // A API é somente por token (sem modo stateful), então sempre há um PersonalAccessToken
        $usuario->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Usuário autenticado
     */
    public function eu(Request $request): UserResource
    {
        /** @var User $usuario */
        $usuario = $request->user();

        return new UserResource($usuario->load('escola'));
    }
}
