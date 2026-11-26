<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

use App\Domain\Avaliacao\Nota;
use DateTimeImmutable;

/**
 * Compara a versão informada pelo cliente (versão que estava em cache quando a
 * operação foi feita offline) com a versão corrente no servidor.
 */
final class ResolvedorDeConflitos
{
    /** Houve alteração concorrente desde que o cliente leu o registro? */
    public function houveAlteracaoConcorrente(int $versaoBase, int $versaoAtual): bool
    {
        return $versaoBase < $versaoAtual;
    }

    /**
     * Aula e frequência: last-write-wins pelo instante em que a alteração foi
     * feita no dispositivo. Sem alteração concorrente, a operação é aplicada.
     */
    public function ultimaEscritaVence(
        int $versaoBase,
        int $versaoAtual,
        DateTimeImmutable $ocorridoEm,
        DateTimeImmutable $alteradoEmAtual,
    ): Decisao {
        if (! $this->houveAlteracaoConcorrente($versaoBase, $versaoAtual)) {
            return Decisao::Aplicar;
        }

        return $ocorridoEm >= $alteradoEmAtual ? Decisao::Aplicar : Decisao::Descartar;
    }

    /**
     * Nota: conflito nunca é aplicado automaticamente. Se o valor proposto for
     * igual ao atual, não há conflito real.
     */
    public function nota(int $versaoBase, int $versaoAtual, Nota $valorAtual, Nota $valorProposto): Decisao
    {
        if ($valorAtual->igual($valorProposto)) {
            return Decisao::SemAlteracao;
        }

        if (! $this->houveAlteracaoConcorrente($versaoBase, $versaoAtual)) {
            return Decisao::Aplicar;
        }

        return Decisao::Conflito;
    }
}
