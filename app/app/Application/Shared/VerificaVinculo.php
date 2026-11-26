<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Acesso\Contracts\VinculoRepository;
use App\Domain\Acesso\RegraAcesso;
use App\Domain\Shared\Exceptions\AcessoNegado;
use App\Domain\Shared\Exceptions\RegistroNaoEncontrado;

/**
 * Garante que só o professor vinculado à turma/disciplina registra o diário.
 */
final readonly class VerificaVinculo
{
    public function __construct(
        private VinculoRepository $vinculos,
        private RegraAcesso $regra,
    ) {}

    public function garantirRegistroDiario(ContextoUsuario $usuario, int $alocacaoId): void
    {
        $professor = $this->vinculos->professorDaAlocacao($alocacaoId);

        if ($professor === null) {
            throw new RegistroNaoEncontrado('Turma/disciplina não encontrada.');
        }

        if (! $this->regra->podeRegistrarDiario($usuario->papel, $usuario->id, $professor)) {
            throw new AcessoNegado('Você não está vinculado a esta turma/disciplina.');
        }
    }
}
