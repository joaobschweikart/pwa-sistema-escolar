<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao;

/**
 * Critérios de aprovação. Padrões: média mínima 6,0 e frequência mínima de 75%
 * (art. 24, VI, da LDB — Lei nº 9.394/1996). Os valores são configuráveis.
 */
final readonly class CriteriosAprovacao
{
    public function __construct(
        public float $mediaMinima = 6.0,
        public float $frequenciaMinima = 75.0,
    ) {}

    public function situacao(?float $media, ?float $frequenciaPercentual): SituacaoAluno
    {
        if ($frequenciaPercentual !== null && $frequenciaPercentual < $this->frequenciaMinima) {
            return SituacaoAluno::ReprovadoPorFrequencia;
        }

        if ($media === null) {
            return SituacaoAluno::SemLancamentos;
        }

        return $media >= $this->mediaMinima ? SituacaoAluno::Aprovado : SituacaoAluno::Recuperacao;
    }
}
