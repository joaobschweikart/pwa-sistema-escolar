<?php

declare(strict_types=1);

namespace App\Application\Sincronizacao;

use App\Application\Shared\ContextoUsuario;
use App\Domain\Acesso\Contracts\VinculoRepository;
use App\Domain\Acesso\RegraAcesso;
use App\Domain\Avaliacao\Contracts\AuditoriaNotaRepository;
use App\Domain\Avaliacao\Contracts\NotaRepository;
use App\Domain\Avaliacao\Nota;
use App\Domain\Avaliacao\OrigemAlteracao;
use App\Domain\Avaliacao\RegistroAuditoriaNota;
use App\Domain\Shared\Contracts\GerenciadorTransacao;
use App\Domain\Shared\Contracts\Relogio;
use App\Domain\Shared\Exceptions\AcessoNegado;
use App\Domain\Shared\Exceptions\RegistroNaoEncontrado;
use App\Domain\Sincronizacao\Contracts\ConflitoNotaRepository;
use App\Domain\Sincronizacao\ResolucaoConflito;
use App\Domain\Sincronizacao\ResultadoOperacao;

/**
 * Caso de uso: o gestor decide um conflito de nota. A alteração resultante é
 * auditada com origem "resolucao_conflito".
 */
final readonly class ResolverConflitoNota
{
    public function __construct(
        private ConflitoNotaRepository $conflitos,
        private NotaRepository $notas,
        private AuditoriaNotaRepository $auditoria,
        private VinculoRepository $vinculos,
        private RegraAcesso $regra,
        private GerenciadorTransacao $transacao,
        private Relogio $relogio,
    ) {}

    public function executar(ContextoUsuario $usuario, int $conflitoId, ResolucaoConflito $resolucao): ResultadoOperacao
    {
        return $this->transacao->executar(function () use ($usuario, $conflitoId, $resolucao): ResultadoOperacao {
            $conflito = $this->conflitos->buscarPendenteParaAtualizar($conflitoId)
                ?? throw new RegistroNaoEncontrado('Conflito não encontrado ou já resolvido.');

            $alocacao = $this->vinculos->alocacaoDaAvaliacao($conflito->avaliacaoId);
            $escola = $alocacao !== null ? $this->vinculos->escolaDaAlocacao($alocacao) : null;

            if ($escola === null || ! $this->regra->podeResolverConflito($usuario->papel, $usuario->escolaId, $escola)) {
                throw new AcessoNegado('Somente o gestor da escola pode resolver este conflito.');
            }

            $agora = $this->relogio->agora();

            if ($resolucao === ResolucaoConflito::AplicarProposto) {
                $nota = $this->notas->buscarParaAtualizar($conflito->avaliacaoId, $conflito->matriculaId)
                    ?? throw new RegistroNaoEncontrado('Nota não encontrada.');

                $anterior = $nota->valor->valor;
                $nota->alterar(new Nota($conflito->valorProposto), $agora, $usuario->id);
                $nota = $this->notas->salvar($nota);

                $this->auditoria->registrar(new RegistroAuditoriaNota(
                    notaId: $nota->id,
                    avaliacaoId: $nota->avaliacaoId,
                    matriculaId: $nota->matriculaId,
                    usuarioId: $usuario->id,
                    valorAnterior: $anterior,
                    valorNovo: $nota->valor->valor,
                    origem: OrigemAlteracao::ResolucaoConflito,
                    ocorridoEm: $agora,
                    operacaoUuid: $conflito->operacaoUuid,
                ));
            }

            $this->conflitos->marcarResolvido($conflitoId, $usuario->id, $resolucao, $agora);

            return ResultadoOperacao::aplicada('Conflito resolvido: '.mb_strtolower($resolucao->rotulo()).'.');
        });
    }
}
