<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use RuntimeException;

/** Exceção base para violações de regra do domínio. */
abstract class RegraDeNegocioException extends RuntimeException {}
