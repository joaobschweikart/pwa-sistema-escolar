<?php

declare(strict_types=1);

namespace App\Application\Sincronizacao;

use App\Application\Avaliacao\LancarNota;
use App\Application\Avaliacao\LancarNotaComando;
use App\Application\Diario\RegistrarAula;
use App\Application\Diario\RegistrarAulaComando;
use App\Application\Diario\RegistrarFrequencia;
use App\Application\Shared\ContextoUsuario;
use App\Domain\Shared\Contracts\GerenciadorTransacao;
use App\Domain\Shared\Exceptions\RegraDeNegocioException;
use App\Domain\Sincronizacao\ConflitoNota;
use App\Domain\Sincronizacao\Contracts\ConflitoNotaRepository;
use App\Domain\Sincronizacao\Contracts\OperacaoSincronizacaoRepository;
use App\Domain\Sincronizacao\ResultadoOperacao;
use App\Domain\Sincronizacao\StatusOperacao;

/**
 * Caso de uso: processar um lote de lançamentos feitos offline.
 *
 *  - Idempotência: o UUID da operação é chave única; reenviar o lote devolve o
 *    resultado já registrado, sem novo efeito.
 *  - Cada operação é atômica: o efeito e o registro do UUID são gravados na
 *    mesma transação.
 *  - Conflito de nota não é aplicado: vai para a fila de conflitos do gestor.
 */
final readonly class ProcessarLoteSincronizacao
{
    public function __construct(
        private OperacaoSincronizacaoRepository $operacoes,
        private ConflitoNotaRepository $conflitos,
        private GerenciadorTransacao $transacao,
        private RegistrarAula $registrarAula,
        private RegistrarFrequencia $registrarFrequencia,
        private LancarNota $lancarNota,
    ) {}

    /**
     * @param  list<OperacaoSincronizacao>  $operacoes
     * @return list<array{uuid: string, tipo: string|null, duplicada: bool, status: string, mensagem: string, dados: array<string, mixed>}>
     */
    public function executar(ContextoUsuario $usuario, array $operacoes): array
    {
        $resultados = [];

        foreach ($operacoes as $operacao) {
            [$resultado, $duplicada] = $this->processar($usuario, $operacao);

            $resultados[] = [
                'uuid' => $operacao->uuid,
                'tipo' => $operacao->tipo?->value,
                'duplicada' => $duplicada,
            ] + $resultado->paraArray();
        }

        return $resultados;
    }

    /** @return array{0: ResultadoOperacao, 1: bool} */
    private function processar(ContextoUsuario $usuario, OperacaoSincronizacao $operacao): array
    {
        if ($operacao->erro !== null || $operacao->comando === null || $operacao->tipo === null) {
            return [ResultadoOperacao::rejeitada($operacao->erro ?? 'Operação inválida.'), false];
        }

        $existente = $this->operacoes->resultadoDe($operacao->uuid);
        if ($existente !== null && $existente->status !== StatusOperacao::Processando) {
            return [$existente, true];
        }

        $tipo = $operacao->tipo;
        $resultado = $this->transacao->executar(function () use ($usuario, $operacao, $tipo): ?ResultadoOperacao {
            if (! $this->operacoes->reservar($operacao->uuid, $usuario->id, $tipo)) {
                return null; // outra requisição concorrente já reservou o UUID
            }

            try {
                $resultado = $this->transacao->executar(fn (): ResultadoOperacao => $this->aplicar($usuario, $operacao));
            } catch (RegraDeNegocioException $e) {
                $resultado = ResultadoOperacao::rejeitada($e->getMessage());
            }

            if ($resultado->status === StatusOperacao::Conflito && $operacao->comando instanceof LancarNotaComando) {
                $resultado = $this->registrarConflito($usuario, $operacao->uuid, $operacao->comando, $resultado);
            }

            $this->operacoes->concluir($operacao->uuid, $resultado);

            return $resultado;
        });

        if ($resultado === null) {
            return [$this->operacoes->resultadoDe($operacao->uuid) ?? ResultadoOperacao::rejeitada('Operação em processamento.'), true];
        }

        return [$resultado, false];
    }

    private function aplicar(ContextoUsuario $usuario, OperacaoSincronizacao $operacao): ResultadoOperacao
    {
        $comando = $operacao->comando;

        return match (true) {
            $comando instanceof RegistrarAulaComando => $this->registrarAula->executar($usuario, $comando),
            $comando instanceof LancarNotaComando => $this->lancarNota->executar($usuario, $comando),
            default => $this->registrarFrequencia->executar($usuario, $comando),
        };
    }

    private function registrarConflito(ContextoUsuario $usuario, string $uuid, LancarNotaComando $c, ResultadoOperacao $resultado): ResultadoOperacao
    {
        $valorServidor = $resultado->dados['valor_servidor'] ?? 0.0;
        $versaoServidor = $resultado->dados['versao_servidor'] ?? 0;

        $id = $this->conflitos->registrar(new ConflitoNota(
            operacaoUuid: $uuid,
            avaliacaoId: $c->avaliacaoId,
            matriculaId: $c->matriculaId,
            professorId: $usuario->id,
            valorServidor: is_numeric($valorServidor) ? (float) $valorServidor : 0.0,
            valorProposto: $c->valor,
            versaoBase: $c->versaoBase,
            versaoServidor: is_numeric($versaoServidor) ? (int) $versaoServidor : 0,
            propostoEm: $c->ocorridoEm,
        ));

        return ResultadoOperacao::conflito($resultado->mensagem, $resultado->dados + ['conflito_id' => $id]);
    }
}
