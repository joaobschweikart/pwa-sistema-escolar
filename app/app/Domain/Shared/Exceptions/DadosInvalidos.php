<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class DadosInvalidos extends RegraDeNegocioException
{
    public function __construct(string $message = 'Dados inválidos.')
    {
        parent::__construct($message);
    }
}
