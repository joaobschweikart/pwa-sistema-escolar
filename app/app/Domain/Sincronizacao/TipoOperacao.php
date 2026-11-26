<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

enum TipoOperacao: string
{
    case RegistrarAula = 'aula.registrar';
    case RegistrarFrequencia = 'frequencia.registrar';
    case LancarNota = 'nota.lancar';

    /** Estratégia de resolução de conflito aplicada a cada tipo (capítulo 2 da modelagem). */
    public function estrategia(): EstrategiaConflito
    {
        return match ($this) {
            self::RegistrarAula, self::RegistrarFrequencia => EstrategiaConflito::UltimaEscritaVence,
            self::LancarNota => EstrategiaConflito::DecisaoDoGestor,
        };
    }
}
