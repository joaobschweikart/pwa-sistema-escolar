<?php

declare(strict_types=1);

namespace App\Http\Policies;

use App\Domain\Acesso\RegraAcesso;
use App\Infrastructure\Persistence\Models\Disciplina;
use App\Infrastructure\Persistence\Models\User;

final readonly class DisciplinaPolicy
{
    public function __construct(private RegraAcesso $regra) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->regra->podeAdministrarRede($user->papel);
    }

    public function update(User $user, Disciplina $disciplina): bool
    {
        return $this->regra->podeAdministrarRede($user->papel);
    }

    public function delete(User $user, Disciplina $disciplina): bool
    {
        return $this->regra->podeAdministrarRede($user->papel);
    }
}
