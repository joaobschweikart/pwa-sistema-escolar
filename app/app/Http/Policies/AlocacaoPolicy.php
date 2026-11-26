<?php

declare(strict_types=1);

namespace App\Http\Policies;

use App\Domain\Acesso\RegraAcesso;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\User;

/**
 * Vínculo professor ↔ turma/disciplina.
 */
final readonly class AlocacaoPolicy
{
    public function __construct(private RegraAcesso $regra) {}

    public function view(User $user, Alocacao $alocacao): bool
    {
        return $this->regra->podeConsultarAlocacao(
            $user->papel,
            $user->id,
            $user->escola_id,
            $alocacao->turma->escola_id,
            $alocacao->professor_id,
        );
    }

    /** Registrar aula, frequência, avaliações e notas: só o professor vinculado. */
    public function registrarDiario(User $user, Alocacao $alocacao): bool
    {
        return $this->regra->podeRegistrarDiario($user->papel, $user->id, $alocacao->professor_id);
    }

    public function create(User $user): bool
    {
        return ! $user->ehProfessor();
    }

    public function delete(User $user, Alocacao $alocacao): bool
    {
        return $this->regra->podeGerirEscola($user->papel, $user->escola_id, $alocacao->turma->escola_id);
    }
}
