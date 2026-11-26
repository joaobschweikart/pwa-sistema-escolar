<?php

declare(strict_types=1);

namespace App\Http\Sync;

use App\Application\Avaliacao\LancarNotaComando;
use App\Application\Diario\RegistrarAulaComando;
use App\Application\Diario\RegistrarFrequenciaComando;
use App\Application\Sincronizacao\OperacaoSincronizacao;
use App\Domain\Avaliacao\OrigemAlteracao;
use App\Domain\Sincronizacao\TipoOperacao;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Converte as operações recebidas no lote em comandos da camada de aplicação.
 * Operações com dados inválidos viram rejeições individuais.
 */
final class MapeadorOperacoes
{
    /** Tolerância para relógio adiantado no dispositivo. */
    private const int TOLERANCIA_RELOGIO_SEGUNDOS = 300;

    /**
     * @param  list<array<string, mixed>>  $operacoes
     * @return list<OperacaoSincronizacao>
     */
    public function mapear(array $operacoes): array
    {
        return array_map(fn (array $op): OperacaoSincronizacao => $this->mapearUma($op), $operacoes);
    }

    /** @param array<string, mixed> $op */
    private function mapearUma(array $op): OperacaoSincronizacao
    {
        $uuid = (string) $op['uuid'];
        $tipo = TipoOperacao::from((string) $op['tipo']);
        /** @var array<string, mixed> $dados */
        $dados = (array) $op['dados'];
        $versaoBase = (int) ($op['versao_base'] ?? 0);
        $ocorridoEm = $this->instante((string) $op['ocorrido_em']);

        $validador = Validator::make($dados, $this->regras($tipo));
        if ($validador->fails()) {
            return OperacaoSincronizacao::invalida($uuid, $tipo, 'Dados inválidos: '.implode(' ', $validador->errors()->all()));
        }

        $comando = match ($tipo) {
            TipoOperacao::RegistrarAula => new RegistrarAulaComando(
                aulaId: (string) $dados['aula_id'],
                alocacaoId: (int) $dados['alocacao_id'],
                data: new DateTimeImmutable((string) $dados['data']),
                conteudo: trim((string) $dados['conteudo']),
                observacoes: isset($dados['observacoes']) ? (string) $dados['observacoes'] : null,
                versaoBase: $versaoBase,
                ocorridoEm: $ocorridoEm,
            ),
            TipoOperacao::RegistrarFrequencia => new RegistrarFrequenciaComando(
                aulaId: (string) $dados['aula_id'],
                registros: array_values(array_map(fn (array $r): array => [
                    'matricula_id' => (int) $r['matricula_id'],
                    'presente' => (bool) $r['presente'],
                    'versao_base' => (int) ($r['versao_base'] ?? 0),
                ], (array) $dados['registros'])),
                ocorridoEm: $ocorridoEm,
            ),
            TipoOperacao::LancarNota => new LancarNotaComando(
                avaliacaoId: (int) $dados['avaliacao_id'],
                matriculaId: (int) $dados['matricula_id'],
                valor: (float) $dados['valor'],
                versaoBase: $versaoBase,
                ocorridoEm: $ocorridoEm,
                origem: OrigemAlteracao::Sincronizacao,
                operacaoUuid: $uuid,
            ),
        };

        return new OperacaoSincronizacao($uuid, $tipo, $comando);
    }

    /** @return array<string, mixed> */
    private function regras(TipoOperacao $tipo): array
    {
        return match ($tipo) {
            TipoOperacao::RegistrarAula => [
                'aula_id' => ['required', 'uuid'],
                'alocacao_id' => ['required', 'integer'],
                'data' => ['required', 'date_format:Y-m-d'],
                'conteudo' => ['required', 'string', 'max:5000'],
                'observacoes' => ['nullable', 'string', 'max:2000'],
            ],
            TipoOperacao::RegistrarFrequencia => [
                'aula_id' => ['required', 'uuid'],
                'registros' => ['required', 'array', 'min:1', 'max:100'],
                'registros.*.matricula_id' => ['required', 'integer', 'distinct'],
                'registros.*.presente' => ['required', 'boolean'],
                'registros.*.versao_base' => ['nullable', 'integer', 'min:0'],
            ],
            TipoOperacao::LancarNota => [
                'avaliacao_id' => ['required', 'integer'],
                'matricula_id' => ['required', 'integer'],
                'valor' => ['required', 'numeric', 'between:0,10'],
            ],
        };
    }

    /** Instante da alteração no dispositivo, limitado ao relógio do servidor. */
    private function instante(string $valor): DateTimeImmutable
    {
        $instante = Carbon::parse($valor)->setTimezone(config('app.timezone'));
        $limite = Carbon::now()->addSeconds(self::TOLERANCIA_RELOGIO_SEGUNDOS);

        return ($instante->greaterThan($limite) ? Carbon::now() : $instante)->toDateTimeImmutable();
    }
}
