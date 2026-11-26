<?php

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/** Autentica na API com token Sanctum. */
function comoApi(User $usuario): User
{
    Sanctum::actingAs($usuario);

    return $usuario;
}

/**
 * Monta uma operação de sincronização.
 *
 * @param  array<string, mixed>  $dados
 * @return array<string, mixed>
 */
function operacao(string $tipo, array $dados, int $versaoBase = 0, ?string $ocorridoEm = null, ?string $uuid = null): array
{
    return [
        'uuid' => $uuid ?? (string) Str::uuid(),
        'tipo' => $tipo,
        'ocorrido_em' => $ocorridoEm ?? now()->toIso8601String(),
        'versao_base' => $versaoBase,
        'dados' => $dados,
    ];
}
