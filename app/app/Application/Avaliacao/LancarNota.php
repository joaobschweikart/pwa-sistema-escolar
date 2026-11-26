<?php

declare(strict_types=1);

namespace App\Application\Avaliacao;

use App\Application\Shared\ContextoUsuario;
use App\Application\Shared\VerificaVinculo;
use App\Domain\Acesso\Contracts\VinculoRepository;
use App\Domain\Avaliacao\Contracts\AuditoriaNotaRepository;
use App\Domain\Avaliacao\Contracts\NotaRepository;
use App\Domain\Avaliacao\LancamentoNota;
use App\Domain\Avaliacao\Nota;
use App\Domain\Avaliacao\RegistroAuditoriaNota;
use App\Domain\Shared\Contracts\Relogio;
use App\Domain\Shared\Exceptions\DadosInvalidos;
use App\Domain\Shared\Exceptions\RegistroNaoEncontrado;
use App\Domain\Sincronizacao\Decisao;
use App\Domain\Sincronizacao\ResolvedorDeConflitos;
use App\Domain\Sincronizacao\ResultadoOperacao;

/**
 * Caso de uso: lançar a nota de um aluno em uma avaliação.
 * Toda alteração é auditada. Conflito de versão NÃO é aplicado.
 */
final readonly class LancarNota
{
    public function __construct(
        private NotaRepository $notas,
        private AuditoriaNotaRepository $auditoria,
        private VinculoRepository $vinculos,
        private VerificaVinculo $vinculo,
        private ResolvedorDeConflitos $resolvedor,
        private Relogio $relogio,
    ) {}

    public function executar(ContextoUsuario $usuario, LancarNotaComando $c): ResultadoOperacao
    {
        $alocacaoId = $this->vinculos->alocacaoDaAvaliacao($c->avaliacaoId);

        if ($alocacaoId === null) {
            throw new RegistroNaoEncontrado('Avaliação não encontrada.');
        }

        $this->vinculo->garantirRegistroDiario($usuario, $alocacaoId);

        if (! $this->vinculos->matriculaAtivaNaAlocacao($c->matriculaId, $alocacaoId)) {
            throw new DadosInvalidos('O aluno não está matriculado nesta turma.');
        }

        $proposta = new Nota($c->valor);
        $nota = $this->notas->buscarParaAtualizar($c->avaliacaoId, $c->matriculaId);

        if ($nota === null) {
            $nota = $this->notas->salvar(LancamentoNota::novo($c->avaliacaoId, $c->matriculaId, $proposta, $c->ocorridoEm, $usuario->id));
            $this->auditar($nota, null, $usuario, $c);

            return ResultadoOperacao::aplicada('Nota lançada.', $this->dados($nota));
        }

        $decisao = $this->resolvedor->nota($c->versaoBase, $nota->versao, $nota->valor, $proposta);

        if ($decisao === Decisao::SemAlteracao) {
            return ResultadoOperacao::aplicada('Nota sem alteração.', $this->dados($nota));
        }

        if ($decisao === Decisao::Conflito) {
            return ResultadoOperacao::conflito(
                'A nota foi alterada no servidor depois da sua última sincronização. O gestor decidirá qual valor vale.',
                $this->dados($nota) + [
                    'valor_servidor' => $nota->valor->valor,
                    'versao_servidor' => $nota->versao,
                    'valor_proposto' => $proposta->valor,
                    'versao_base' => $c->versaoBase,
                ],
            );
        }

        $anterior = $nota->valor->valor;
        $nota->alterar($proposta, $c->ocorridoEm, $usuario->id);
        $nota = $this->notas->salvar($nota);
        $this->auditar($nota, $anterior, $usuario, $c);

        return ResultadoOperacao::aplicada('Nota alterada.', $this->dados($nota));
    }

    private function auditar(LancamentoNota $nota, ?float $anterior, ContextoUsuario $usuario, LancarNotaComando $c): void
    {
        $this->auditoria->registrar(new RegistroAuditoriaNota(
            notaId: $nota->id,
            avaliacaoId: $nota->avaliacaoId,
            matriculaId: $nota->matriculaId,
            usuarioId: $usuario->id,
            valorAnterior: $anterior,
            valorNovo: $nota->valor->valor,
            origem: $c->origem,
            ocorridoEm: $this->relogio->agora(),
            operacaoUuid: $c->operacaoUuid,
        ));
    }

    /** @return array<string, mixed> */
    private function dados(LancamentoNota $nota): array
    {
        return [
            'avaliacao_id' => $nota->avaliacaoId,
            'matricula_id' => $nota->matriculaId,
            'valor' => $nota->valor->valor,
            'versao' => $nota->versao,
        ];
    }
}
