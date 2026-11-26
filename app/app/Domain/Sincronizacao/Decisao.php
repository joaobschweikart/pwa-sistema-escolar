<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

enum Decisao: string
{
    case Aplicar = 'aplicar';
    case Descartar = 'descartar';
    case Conflito = 'conflito';
    case SemAlteracao = 'sem_alteracao';
}
