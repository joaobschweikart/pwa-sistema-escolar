<?php

use App\Domain\Avaliacao\CalculadoraMedia;
use App\Domain\Avaliacao\Nota;
use App\Domain\Avaliacao\NotaPonderada;
use App\Domain\Shared\Exceptions\DadosInvalidos;

$n = fn (float $valor, float $peso = 1) => new NotaPonderada(new Nota($valor), $peso);

it('retorna null quando não há notas', function () {
    expect((new CalculadoraMedia)->calcular([]))->toBeNull();
});

it('calcula a média aritmética quando todos os pesos são 1', function () use ($n) {
    expect((new CalculadoraMedia)->calcular([$n(6), $n(8), $n(10)]))->toBe(8.0);
});

it('calcula a média ponderada pelos pesos', function () use ($n) {
    // (5×2 + 8×1) / 3 = 6,0
    expect((new CalculadoraMedia)->calcular([$n(5, 2), $n(8, 1)]))->toBe(6.0);
});

it('arredonda para uma casa decimal (meio para cima)', function () use ($n) {
    // (7 + 8 + 8,5) / 3 = 7,8333…
    expect((new CalculadoraMedia)->calcular([$n(7), $n(8), $n(8.5)]))->toBe(7.8)
        // (6,5 + 7) / 2 = 6,75 -> 6,8
        ->and((new CalculadoraMedia)->calcular([$n(6.5), $n(7)]))->toBe(6.8);
});

it('rejeita nota fora do intervalo de 0 a 10', function (float $valor) {
    new Nota($valor);
})->with([-0.1, 10.01, 11])->throws(DadosInvalidos::class);

it('rejeita peso zero ou negativo', function () {
    new NotaPonderada(new Nota(7), 0);
})->throws(DadosInvalidos::class);
