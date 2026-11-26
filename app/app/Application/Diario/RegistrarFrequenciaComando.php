<?php

declare(strict_types=1);

namespace App\Application\Diario;

use DateTimeImmutable;

final readonly class RegistrarFrequenciaComando
{
    /**
     * @param  list<array{matricula_id: int, presente: bool, versao_base: int}>  $registros
     */
    public function __construct(
        public string $aulaId,
        public array $registros,
        public DateTimeImmutable $ocorridoEm,
    ) {}
}
