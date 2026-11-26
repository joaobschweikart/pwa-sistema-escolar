<?php

declare(strict_types=1);

namespace App\Application\Avaliacao;

use App\Domain\Avaliacao\CalculadoraMedia;
use App\Domain\Avaliacao\Contracts\DadosBoletimRepository;
use App\Domain\Avaliacao\CriteriosAprovacao;
use App\Domain\Avaliacao\Nota;
use App\Domain\Avaliacao\NotaPonderada;
use App\Domain\Diario\ApuracaoFrequencia;

/**
 * Caso de uso: consultar médias, frequência e situação dos alunos de uma
 * turma em uma disciplina.
 */
final readonly class GerarBoletim
{
    public function __construct(
        private DadosBoletimRepository $dados,
        private CalculadoraMedia $calculadora,
        private ApuracaoFrequencia $frequencia,
        private CriteriosAprovacao $criterios,
    ) {}

    /**
     * @return list<array{matricula_id: int, aluno_id: int, aluno: string, numero_chamada: int|null, media: float|null, frequencia: float|null, faltas: int, situacao: string, situacao_rotulo: string}>
     */
    public function executar(int $alocacaoId, ?int $periodo = null): array
    {
        $notasPorMatricula = [];
        foreach ($this->dados->notasComPeso($alocacaoId, $periodo) as $nota) {
            $notasPorMatricula[$nota['matricula_id']][] = new NotaPonderada(new Nota($nota['valor']), $nota['peso']);
        }

        $totalAulas = $this->dados->totalAulas($alocacaoId);
        $presencas = $this->dados->presencasPorMatricula($alocacaoId);
        $linhas = [];

        foreach ($this->dados->matriculasAtivas($alocacaoId) as $m) {
            $media = $this->calculadora->calcular($notasPorMatricula[$m['matricula_id']] ?? []);
            $presente = min($presencas[$m['matricula_id']] ?? 0, $totalAulas);
            $percentual = $this->frequencia->percentual($presente, $totalAulas);
            $situacao = $this->criterios->situacao($media, $percentual);

            $linhas[] = $m + [
                'media' => $media,
                'frequencia' => $percentual,
                'faltas' => $this->frequencia->faltas($presente, $totalAulas),
                'situacao' => $situacao->value,
                'situacao_rotulo' => $situacao->rotulo(),
            ];
        }

        return $linhas;
    }
}
