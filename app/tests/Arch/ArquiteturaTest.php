<?php

/*
| Regras do diagrama de pacotes (doc/diagramas/01-pacotes-arquitetura-aplicacao.puml):
|   app.http -> app.application -> app.domain <- app.infrastructure
| O domínio não depende de nenhuma outra camada nem do framework.
*/

arch('app.domain não depende de Laravel, Eloquent nem de outras camadas')
    ->expect('App\Domain')
    ->not->toUse([
        'Illuminate',
        'Laravel',
        'Carbon',
        'App\Application',
        'App\Http',
        'App\Infrastructure',
        'App\Providers',
    ]);

arch('app.domain não usa helpers globais do Laravel')
    ->expect(['app', 'config', 'env', 'now', 'auth', 'request', 'response', 'collect', 'logger', 'event', 'dispatch'])
    ->not->toBeUsedIn('App\Domain');

arch('contratos do domínio são interfaces')
    ->expect('App\Domain\*\Contracts')
    ->toBeInterfaces();

arch('app.application não depende de app.http nem do Eloquent')
    ->expect('App\Application')
    ->not->toUse(['App\Http', 'App\Infrastructure', 'Illuminate\Database', 'Illuminate\Http']);

arch('app.infrastructure não depende de app.http')
    ->expect('App\Infrastructure')
    ->not->toUse(['App\Http']);

arch('repositórios Eloquent são classes finais')
    ->expect('App\Infrastructure\Persistence\Repositories')
    ->toBeFinal()
    ->toHaveSuffix('Repository');

test('cada repositório Eloquent implementa um contrato declarado em app.domain', function () {
    $arquivos = glob(app_path('Infrastructure/Persistence/Repositories/*.php'));
    expect($arquivos)->not->toBeEmpty();

    foreach ($arquivos as $arquivo) {
        $classe = 'App\\Infrastructure\\Persistence\\Repositories\\'.basename($arquivo, '.php');
        $contratos = array_filter(
            class_implements($classe),
            fn (string $i) => preg_match('/^App\\\\Domain\\\\\w+\\\\Contracts\\\\/', $i) === 1,
        );
        expect($contratos)->not->toBeEmpty("{$classe} não implementa contrato do domínio");
    }
});

test('o ServiceProvider liga cada contrato do domínio a uma implementação', function () {
    foreach (glob(app_path('Domain/*/Contracts/*.php')) as $arquivo) {
        $modulo = basename(dirname($arquivo, 2));
        $contrato = "App\\Domain\\{$modulo}\\Contracts\\".basename($arquivo, '.php');
        expect(app()->bound($contrato))->toBeTrue("{$contrato} sem binding")
            ->and(app($contrato))->toBeInstanceOf($contrato);
    }
});

arch('models Eloquent ficam somente na infraestrutura')
    ->expect('Illuminate\Database\Eloquent\Model')
    ->toOnlyBeUsedIn(['App\Infrastructure', 'Database']);

arch('código de produção usa strict types no domínio e na aplicação')
    ->expect(['App\Domain', 'App\Application'])
    ->toUseStrictTypes();

arch('sem funções de depuração esquecidas')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
