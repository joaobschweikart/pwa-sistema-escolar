<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class AcessoNegado extends RegraDeNegocioException
{
    public function __construct(string $message = 'Usuário sem permissão para a operação.')
    {
        parent::__construct($message);
    }
}
