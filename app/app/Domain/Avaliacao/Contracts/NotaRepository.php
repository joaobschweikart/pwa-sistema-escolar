<?php

declare(strict_types=1);

namespace App\Domain\Avaliacao\Contracts;

use App\Domain\Avaliacao\LancamentoNota;

interface NotaRepository
{
    /** Busca com bloqueio para atualização (evita escrita concorrente). */
    public function buscarParaAtualizar(int $avaliacaoId, int $matriculaId): ?LancamentoNota;

    public function salvar(LancamentoNota $nota): LancamentoNota;
}
