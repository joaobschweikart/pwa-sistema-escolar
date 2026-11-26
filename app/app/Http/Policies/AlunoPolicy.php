<?php

declare(strict_types=1);

namespace App\Http\Policies;

use App\Domain\Acesso\RegraAcesso;
use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\User;

final readonly class AlunoPolicy
{
    public function __construct(private RegraAcesso $regra) {}

    public function viewAny(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function view(User $user, Aluno $aluno): bool
    {
        return $this->regra->podeGerirEscola($user->papel, $user->escola_id, $aluno->escola_id);
    }

    public function create(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function update(User $user, Aluno $aluno): bool
    {
        return $this->view($user, $aluno);
    }

    public function delete(User $user, Aluno $aluno): bool
    {
        return $this->view($user, $aluno);
    }
}
