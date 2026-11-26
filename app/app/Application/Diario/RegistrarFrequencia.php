<?php

declare(strict_types=1);

namespace App\Application\Diario;

use App\Application\Shared\ContextoUsuario;
use App\Application\Shared\VerificaVinculo;
use App\Domain\Acesso\Contracts\VinculoRepository;
use App\Domain\Diario\Contracts\FrequenciaRepository;
use App\Domain\Diario\RegistroFrequencia;
use App\Domain\Shared\Exceptions\DadosInvalidos;
use App\Domain\Shared\Exceptions\RegistroNaoEncontrado;
use App\Domain\Sincronizacao\Decisao;
use App\Domain\Sincronizacao\ResolvedorDeConflitos;
use App\Domain\Sincronizacao\ResultadoOperacao;

/**
 * Caso de uso: lançar a chamada (presenças/faltas) de uma aula.
 * Cada registro é resolvido por last-write-wins.
 */
final readonly class RegistrarFrequencia
{
    public function __construct(
        private FrequenciaRepository $frequencias,
        private VinculoRepository $vinculos,
        private VerificaVinculo $vinculo,
        private ResolvedorDeConflitos $resolvedor,
    ) {}

    public function executar(ContextoUsuario $usuario, RegistrarFrequenciaComando $c): ResultadoOperacao
    {
        $alocacaoId = $this->vinculos->alocacaoDaAula($c->aulaId);

        if ($alocacaoId === null) {
            throw new RegistroNaoEncontrado('Aula não encontrada. Registre a aula antes da frequência.');
        }

        $this->vinculo->garantirRegistroDiario($usuario, $alocacaoId);

        if ($c->registros === []) {
            throw new DadosInvalidos('Informe ao menos um registro de frequência.');
        }

        foreach ($c->registros as $item) {
            if (! $this->vinculos->matriculaAtivaNaAlocacao($item['matricula_id'], $alocacaoId)) {
                throw new DadosInvalidos("A matrícula {$item['matricula_id']} não pertence a esta turma.");
            }
        }

        $aplicados = 0;
        $descartados = 0;
        $versoes = [];

        foreach ($c->registros as $item) {
            $registro = $this->frequencias->buscarParaAtualizar($c->aulaId, $item['matricula_id']);

            if ($registro === null) {
                $registro = RegistroFrequencia::novo($c->aulaId, $item['matricula_id'], $item['presente'], $c->ocorridoEm);
                $this->frequencias->salvar($registro);
                $aplicados++;
            } elseif ($this->resolvedor->ultimaEscritaVence($item['versao_base'], $registro->versao, $c->ocorridoEm, $registro->alteradoEm) === Decisao::Descartar) {
                $descartados++;
            } else {
                if ($registro->presente !== $item['presente']) {
                    $registro->alterar($item['presente'], $c->ocorridoEm);
                    $this->frequencias->salvar($registro);
                }
                $aplicados++;
            }

            $versoes[$item['matricula_id']] = $registro->versao;
        }

        $dados = ['aula_id' => $c->aulaId, 'aplicados' => $aplicados, 'descartados' => $descartados, 'versoes' => $versoes];

        if ($aplicados === 0) {
            return ResultadoOperacao::descartada('A frequência no servidor é mais recente (last-write-wins).', $dados);
        }

        $mensagem = $descartados > 0
            ? "Frequência registrada ({$descartados} registro(s) mais recente(s) no servidor mantido(s))."
            : 'Frequência registrada.';

        return ResultadoOperacao::aplicada($mensagem, $dados);
    }
}
