<?php

declare(strict_types=1);

namespace App\Http\Policies;

use App\Domain\Acesso\RegraAcesso;
use App\Infrastructure\Persistence\Models\Turma;
use App\Infrastructure\Persistence\Models\User;

final readonly class TurmaPolicy
{
    public function __construct(private RegraAcesso $regra) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Professor vê apenas turmas em que leciona; gestor, as da sua escola. */
    public function view(User $user, Turma $turma): bool
    {
        if ($user->ehProfessor()) {
            return $turma->alocacoes()->where('professor_id', $user->id)->exists();
        }

        return $this->regra->podeGerirEscola($user->papel, $user->escola_id, $turma->escola_id);
    }

    public function create(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function update(User $user, Turma $turma): bool
    {
        return $this->regra->podeGerirEscola($user->papel, $user->escola_id, $turma->escola_id);
    }

    public function delete(User $user, Turma $turma): bool
    {
        return $this->update($user, $turma);
    }
}
