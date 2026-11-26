<?php

declare(strict_types=1);

namespace App\Application\Diario;

use App\Application\Shared\ContextoUsuario;
use App\Application\Shared\VerificaVinculo;
use App\Domain\Diario\Aula;
use App\Domain\Diario\Contracts\AulaRepository;
use App\Domain\Shared\Exceptions\DadosInvalidos;
use App\Domain\Sincronizacao\Decisao;
use App\Domain\Sincronizacao\ResolvedorDeConflitos;
use App\Domain\Sincronizacao\ResultadoOperacao;

/**
 * Caso de uso: registrar (criar ou alterar) o conteúdo de uma aula.
 * Conflitos são resolvidos por last-write-wins.
 */
final readonly class RegistrarAula
{
    public function __construct(
        private AulaRepository $aulas,
        private VerificaVinculo $vinculo,
        private ResolvedorDeConflitos $resolvedor,
    ) {}

    public function executar(ContextoUsuario $usuario, RegistrarAulaComando $c): ResultadoOperacao
    {
        $this->vinculo->garantirRegistroDiario($usuario, $c->alocacaoId);

        $aula = $this->aulas->buscarParaAtualizar($c->aulaId);

        if ($aula === null) {
            $aula = Aula::nova($c->aulaId, $c->alocacaoId, $c->data, $c->conteudo, $c->observacoes, $c->ocorridoEm, $usuario->id);
            $this->aulas->salvar($aula);

            return ResultadoOperacao::aplicada('Aula registrada.', ['aula_id' => $aula->id, 'versao' => $aula->versao]);
        }

        if ($aula->alocacaoId !== $c->alocacaoId) {
            throw new DadosInvalidos('A aula informada pertence a outra turma/disciplina.');
        }

        $decisao = $this->resolvedor->ultimaEscritaVence($c->versaoBase, $aula->versao, $c->ocorridoEm, $aula->alteradoEm);

        if ($decisao === Decisao::Descartar) {
            return ResultadoOperacao::descartada(
                'Havia uma alteração mais recente desta aula no servidor (last-write-wins).',
                ['aula_id' => $aula->id, 'versao' => $aula->versao],
            );
        }

        $aula->alterar($c->data, $c->conteudo, $c->observacoes, $c->ocorridoEm, $usuario->id);
        $this->aulas->salvar($aula);

        return ResultadoOperacao::aplicada('Aula atualizada.', ['aula_id' => $aula->id, 'versao' => $aula->versao]);
    }
}
