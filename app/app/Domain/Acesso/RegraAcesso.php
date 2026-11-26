<?php

declare(strict_types=1);

namespace App\Domain\Acesso;

use App\Domain\Shared\Papel;

/**
 * Políticas de acesso do domínio (independentes do framework).
 *  - Admin da Secretaria: toda a rede municipal.
 *  - Gestor Escolar: somente a própria escola.
 *  - Professor: somente as turmas/disciplinas às quais está vinculado.
 */
final class RegraAcesso
{
    public function podeAdministrarRede(Papel $papel): bool
    {
        return $papel === Papel::Admin;
    }

    public function podeGerirEscola(Papel $papel, ?int $escolaDoUsuario, int $escola): bool
    {
        return match ($papel) {
            Papel::Admin => true,
            Papel::Gestor => $escolaDoUsuario !== null && $escolaDoUsuario === $escola,
            Papel::Professor => false,
        };
    }

    public function podeConsultarAlocacao(Papel $papel, int $usuarioId, ?int $escolaDoUsuario, int $escolaDaAlocacao, ?int $professorDaAlocacao): bool
    {
        if ($papel === Papel::Professor) {
            return $professorDaAlocacao === $usuarioId;
        }

        return $this->podeGerirEscola($papel, $escolaDoUsuario, $escolaDaAlocacao);
    }

    /** Só o professor vinculado registra aula, frequência e notas. */
    public function podeRegistrarDiario(Papel $papel, int $usuarioId, ?int $professorDaAlocacao): bool
    {
        return $papel === Papel::Professor && $professorDaAlocacao === $usuarioId;
    }

    /** Conflitos de nota são decididos pelo gestor da escola (ou pela secretaria). */
    public function podeResolverConflito(Papel $papel, ?int $escolaDoUsuario, int $escolaDoConflito): bool
    {
        return $this->podeGerirEscola($papel, $escolaDoUsuario, $escolaDoConflito);
    }
}
