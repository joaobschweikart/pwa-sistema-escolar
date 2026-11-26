<?php

declare(strict_types=1);

namespace App\Domain\Sincronizacao;

enum ResolucaoConflito: string
{
    case ManterAtual = 'manter_atual';
    case AplicarProposto = 'aplicar_proposto';

    public function rotulo(): string
    {
        return match ($this) {
            self::ManterAtual => 'Manter o valor atual',
            self::AplicarProposto => 'Aplicar o valor proposto pelo professor',
        };
    }
}
