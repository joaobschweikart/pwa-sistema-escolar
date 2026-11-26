<?php

use App\Domain\Avaliacao\CriteriosAprovacao;
use App\Domain\Avaliacao\SituacaoAluno;
use App\Domain\Diario\ApuracaoFrequencia;
use App\Domain\Shared\Exceptions\DadosInvalidos;

it('calcula o percentual de frequência com duas casas', function () {
    $f = new ApuracaoFrequencia;

    expect($f->percentual(15, 20))->toBe(75.0)
        ->and($f->percentual(2, 3))->toBe(66.67)
        ->and($f->percentual(0, 4))->toBe(0.0)
        ->and($f->faltas(15, 20))->toBe(5);
});

it('retorna null quando ainda não houve aula', function () {
    expect((new ApuracaoFrequencia)->percentual(0, 0))->toBeNull();
});

it('rejeita mais presenças do que aulas', function () {
    (new ApuracaoFrequencia)->percentual(5, 4);
})->throws(DadosInvalidos::class);

it('define a situação pelo critério de média e frequência', function (?float $media, ?float $freq, SituacaoAluno $esperada) {
    expect((new CriteriosAprovacao(6.0, 75.0))->situacao($media, $freq))->toBe($esperada);
})->with([
    'aprovado' => [7.0, 90.0, SituacaoAluno::Aprovado],
    'média no limite' => [6.0, 75.0, SituacaoAluno::Aprovado],
    'recuperação' => [5.9, 100.0, SituacaoAluno::Recuperacao],
    'frequência baixa vence a média' => [9.5, 74.99, SituacaoAluno::ReprovadoPorFrequencia],
    'sem notas' => [null, 80.0, SituacaoAluno::SemLancamentos],
    'sem aulas ainda' => [8.0, null, SituacaoAluno::Aprovado],
]);
