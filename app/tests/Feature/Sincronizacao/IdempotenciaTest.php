<?php

use App\Infrastructure\Persistence\Models\AuditoriaNota;
use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\Frequencia;
use App\Infrastructure\Persistence\Models\Nota;
use App\Infrastructure\Persistence\Models\OperacaoSincronizacao;
use Illuminate\Support\Str;
use Tests\Support\Cenario;

it('aplica um lote com aula, frequência e nota', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $aulaId = (string) Str::uuid();

    $this->postJson('/api/v1/sync', ['operacoes' => [
        operacao('aula.registrar', ['aula_id' => $aulaId, 'alocacao_id' => $c->alocacao->id, 'data' => '2026-11-20', 'conteudo' => 'Frações']),
        operacao('frequencia.registrar', ['aula_id' => $aulaId, 'registros' => $c->matriculas->map(fn ($m) => ['matricula_id' => $m->id, 'presente' => true])->all()]),
        operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $c->matriculas[0]->id, 'valor' => 8.5]),
    ]])
        ->assertOk()
        ->assertJsonPath('resumo.aplicadas', 3)
        ->assertJsonPath('resultados.0.status', 'aplicada')
        ->assertJsonPath('resultados.2.dados.valor', 8.5);

    expect(Aula::query()->find($aulaId))->conteudo->toBe('Frações')->versao->toBe(1)
        ->and(Frequencia::query()->count())->toBe(3)
        ->and(Nota::query()->sole()->valor)->toBe(8.5);
});

it('é idempotente: o mesmo lote enviado 2 vezes produz 1 efeito', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $aulaId = (string) Str::uuid();
    $lote = ['operacoes' => [
        operacao('aula.registrar', ['aula_id' => $aulaId, 'alocacao_id' => $c->alocacao->id, 'data' => '2026-11-20', 'conteudo' => 'Frações']),
        operacao('frequencia.registrar', ['aula_id' => $aulaId, 'registros' => [['matricula_id' => $c->matriculas[0]->id, 'presente' => false]]]),
        operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $c->matriculas[0]->id, 'valor' => 7]),
    ]];

    $primeira = $this->postJson('/api/v1/sync', $lote)->assertOk()->json();
    $segunda = $this->postJson('/api/v1/sync', $lote)->assertOk()->json();

    expect($segunda['resumo']['duplicadas'])->toBe(3)
        ->and(array_column($segunda['resultados'], 'status'))->toBe(array_column($primeira['resultados'], 'status'))
        ->and(Aula::query()->count())->toBe(1)
        ->and(Aula::query()->sole()->versao)->toBe(1)
        ->and(Frequencia::query()->sole()->versao)->toBe(1)
        ->and(Nota::query()->sole()->versao)->toBe(1)
        ->and(AuditoriaNota::query()->count())->toBe(1)
        ->and(OperacaoSincronizacao::query()->count())->toBe(3);
});

it('reenvio parcial: operações já processadas são ignoradas e as novas aplicadas', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $op1 = operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $c->matriculas[0]->id, 'valor' => 6]);
    $op2 = operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $c->matriculas[1]->id, 'valor' => 9]);

    $this->postJson('/api/v1/sync', ['operacoes' => [$op1]])->assertOk();
    $this->postJson('/api/v1/sync', ['operacoes' => [$op1, $op2]])
        ->assertOk()
        ->assertJsonPath('resultados.0.duplicada', true)
        ->assertJsonPath('resultados.1.duplicada', false);

    expect(Nota::query()->count())->toBe(2);
});

it('rejeita operação com dados inválidos sem bloquear o restante do lote', function () {
    $c = Cenario::criar();
    comoApi($c->professor);

    $this->postJson('/api/v1/sync', ['operacoes' => [
        operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $c->matriculas[0]->id, 'valor' => 15]),
        operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $c->matriculas[1]->id, 'valor' => 9]),
    ]])
        ->assertOk()
        ->assertJsonPath('resultados.0.status', 'rejeitada')
        ->assertJsonPath('resultados.1.status', 'aplicada');
});

it('valida a estrutura do lote', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $uuid = (string) Str::uuid();

    $this->postJson('/api/v1/sync', ['operacoes' => []])->assertUnprocessable();
    $this->postJson('/api/v1/sync', ['operacoes' => [operacao('nota.apagar', [])]])->assertUnprocessable()->assertJsonValidationErrors('operacoes.0.tipo');
    $this->postJson('/api/v1/sync', ['operacoes' => [
        operacao('nota.lancar', ['valor' => 1], uuid: $uuid), operacao('nota.lancar', ['valor' => 1], uuid: $uuid),
    ]])->assertUnprocessable()->assertJsonValidationErrors('operacoes.0.uuid');
});

it('exige autenticação', function () {
    $this->postJson('/api/v1/sync', ['operacoes' => []])->assertUnauthorized();
});
