<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

enum OrigemAlteracao: string
{
    case Api = 'api';
    case Sincronizacao = 'sincronizacao';
    case ResolucaoConflito = 'resolucao_conflito';
}
