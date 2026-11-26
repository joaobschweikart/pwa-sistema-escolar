<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

enum EstrategiaConflito: string
{
    /** Last-write-wins: vence a alteração feita por último no dispositivo. */
    case UltimaEscritaVence = 'last_write_wins';

    /** Não aplica: o conflito vai para a fila de decisão do gestor. */
    case DecisaoDoGestor = 'decisao_gestor';
}
