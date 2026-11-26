<?php

declare(strict_types=1);

namespace App\Http\Policies;

use App\Domain\Acesso\RegraAcesso;
use App\Infrastructure\Persistence\Models\ConflitoNota;
use App\Infrastructure\Persistence\Models\User;

final readonly class ConflitoNotaPolicy
{
    public function __construct(private RegraAcesso $regra) {}

    public function viewAny(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function resolver(User $user, ConflitoNota $conflito): bool
    {
        return $this->regra->podeResolverConflito($user->papel, $user->escola_id, $conflito->avaliacao->alocacao->turma->escola_id);
    }
}
