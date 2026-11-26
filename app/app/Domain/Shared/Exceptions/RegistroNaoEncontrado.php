<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class RegistroNaoEncontrado extends RegraDeNegocioException
{
    public function __construct(string $message = 'Registro não encontrado.')
    {
        parent::__construct($message);
    }
}
