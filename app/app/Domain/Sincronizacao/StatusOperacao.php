<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

enum StatusOperacao: string
{
    case Processando = 'processando';
    case Aplicada = 'aplicada';
    /** LWW: havia uma alteração mais recente no servidor; a operação foi descartada. */
    case Descartada = 'descartada';
    /** Conflito de nota encaminhado para decisão do gestor. */
    case Conflito = 'conflito';
    case Rejeitada = 'rejeitada';
}
