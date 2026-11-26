<?php

declare(strict_types=1);

namespace App\Http\Policies;

use App\Domain\Shared\Papel;
use App\Infrastructure\Persistence\Models\User;

/**
 * Admin gerencia todos os usuários; gestor gerencia os professores da sua escola.
 */
final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function create(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function update(User $user, User $alvo): bool
    {
        if ($user->ehAdmin()) {
            return true;
        }

        return $user->ehGestor() && $alvo->papel === Papel::Professor && $alvo->escola_id === $user->escola_id;
    }

    public function delete(User $user, User $alvo): bool
    {
        return $user->id !== $alvo->id && $this->update($user, $alvo);
    }
}
