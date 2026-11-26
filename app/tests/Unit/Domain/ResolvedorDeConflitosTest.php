<?php

use App\Domain\Avaliacao\Nota;
use App\Domain\Sincronizacao\Decisao;
use App\Domain\Sincronizacao\EstrategiaConflito;
use App\Domain\Sincronizacao\ResolvedorDeConflitos;
use App\Domain\Sincronizacao\TipoOperacao;

$r = new ResolvedorDeConflitos;
$t = fn (string $hora) => new DateTimeImmutable("2026-11-20 {$hora}");

it('aplica quando não houve alteração concorrente', function () use ($r, $t) {
    expect($r->ultimaEscritaVence(3, 3, $t('08:00'), $t('09:00')))->toBe(Decisao::Aplicar);
});

it('LWW: com alteração concorrente, vence a escrita mais recente no dispositivo', function () use ($r, $t) {
    expect($r->ultimaEscritaVence(1, 2, $t('10:00'), $t('09:00')))->toBe(Decisao::Aplicar)
        ->and($r->ultimaEscritaVence(1, 2, $t('08:00'), $t('09:00')))->toBe(Decisao::Descartar);
});

it('nota: versão igual aplica; versão defasada gera conflito', function () use ($r) {
    expect($r->nota(2, 2, new Nota(5), new Nota(7)))->toBe(Decisao::Aplicar)
        ->and($r->nota(1, 2, new Nota(5), new Nota(7)))->toBe(Decisao::Conflito);
});

it('nota: mesmo valor não é conflito', function () use ($r) {
    expect($r->nota(1, 4, new Nota(7.5), new Nota(7.5)))->toBe(Decisao::SemAlteracao);
});

it('aula e frequência usam LWW; nota usa decisão do gestor', function () {
    expect(TipoOperacao::RegistrarAula->estrategia())->toBe(EstrategiaConflito::UltimaEscritaVence)
        ->and(TipoOperacao::RegistrarFrequencia->estrategia())->toBe(EstrategiaConflito::UltimaEscritaVence)
        ->and(TipoOperacao::LancarNota->estrategia())->toBe(EstrategiaConflito::DecisaoDoGestor);
});
