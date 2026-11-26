<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

enum SituacaoAluno: string
{
    case Aprovado = 'aprovado';
    case Recuperacao = 'recuperacao';
    case ReprovadoPorFrequencia = 'reprovado_frequencia';
    case SemLancamentos = 'sem_lancamentos';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aprovado => 'Aprovado',
            self::Recuperacao => 'Em recuperação',
            self::ReprovadoPorFrequencia => 'Reprovado por frequência',
            self::SemLancamentos => 'Sem lançamentos',
        };
    }
}
