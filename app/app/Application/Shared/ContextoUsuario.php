<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Shared\Papel;

/** Quem está executando o caso de uso. */
final readonly class ContextoUsuario
{
    public function __construct(
        public int $id,
        public Papel $papel,
        public ?int $escolaId,
    ) {}
}
