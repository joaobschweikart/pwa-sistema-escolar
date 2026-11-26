<?php

declare(strict_types=1);

namespace App\Http\Policies;

use App\Domain\Acesso\RegraAcesso;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\User;

final readonly class EscolaPolicy
{
    public function __construct(private RegraAcesso $regra) {}

    public function viewAny(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function view(User $user, Escola $escola): bool
    {
        return $this->regra->podeGerirEscola($user->papel, $user->escola_id, $escola->id);
    }

    public function create(User $user): bool
    {
        return $this->regra->podeAdministrarRede($user->papel);
    }

    public function update(User $user, Escola $escola): bool
    {
        return $this->regra->podeAdministrarRede($user->papel);
    }

    public function delete(User $user, Escola $escola): bool
    {
        return $this->regra->podeAdministrarRede($user->papel);
    }
}
