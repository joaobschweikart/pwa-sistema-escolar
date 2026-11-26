<?php

use App\Domain\Acesso\RegraAcesso;
use App\Domain\Shared\Papel;

$regra = new RegraAcesso;

it('somente o professor vinculado registra o diário', function () use ($regra) {
    expect($regra->podeRegistrarDiario(Papel::Professor, 10, 10))->toBeTrue()
        ->and($regra->podeRegistrarDiario(Papel::Professor, 10, 11))->toBeFalse()
        ->and($regra->podeRegistrarDiario(Papel::Gestor, 10, 10))->toBeFalse()
        ->and($regra->podeRegistrarDiario(Papel::Admin, 10, 10))->toBeFalse();
});

it('gestor só gere a própria escola; admin gere a rede', function () use ($regra) {
    expect($regra->podeGerirEscola(Papel::Gestor, 1, 1))->toBeTrue()
        ->and($regra->podeGerirEscola(Papel::Gestor, 1, 2))->toBeFalse()
        ->and($regra->podeGerirEscola(Papel::Admin, null, 2))->toBeTrue()
        ->and($regra->podeGerirEscola(Papel::Professor, 1, 1))->toBeFalse();
});

it('professor consulta somente alocação própria', function () use ($regra) {
    expect($regra->podeConsultarAlocacao(Papel::Professor, 5, 1, 1, 5))->toBeTrue()
        ->and($regra->podeConsultarAlocacao(Papel::Professor, 5, 1, 1, 6))->toBeFalse();
});

it('somente o professor opera offline', function () {
    expect(Papel::Professor->operaOffline())->toBeTrue()
        ->and(Papel::Gestor->operaOffline())->toBeFalse();
});
