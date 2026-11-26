<?php

use App\Infrastructure\Persistence\Models\AuditoriaNota;
use App\Infrastructure\Persistence\Models\User;
use Tests\Support\Cenario;

it('audita lançamento e alteração de nota (quem, quando, valor antigo/novo)', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $m = $c->matriculas[0]->id;

    $this->postJson('/api/v1/sync', ['operacoes' => [operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $m, 'valor' => 5])]]);
    $this->putJson("/api/v1/avaliacoes/{$c->avaliacao->id}/notas", ['notas' => [['matricula_id' => $m, 'valor' => 6.5, 'versao' => 1]]])->assertOk();

    $registros = AuditoriaNota::query()->orderBy('id')->get();

    expect($registros)->toHaveCount(2)
        ->and($registros[0])->valor_anterior->toBeNull()->valor_novo->toBe(5.0)->origem->toBe('sincronizacao')->operacao_uuid->not->toBeNull()
        ->and($registros[1])->valor_anterior->toBe(5.0)->valor_novo->toBe(6.5)->origem->toBe('api')
        ->and($registros[1]->usuario_id)->toBe($c->professor->id)
        ->and($registros[1]->ocorrido_em)->not->toBeNull();
});

it('não audita quando a nota não muda', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $notas = ['notas' => [['matricula_id' => $c->matriculas[0]->id, 'valor' => 8]]];

    $this->putJson("/api/v1/avaliacoes/{$c->avaliacao->id}/notas", $notas)->assertOk();
    $this->putJson("/api/v1/avaliacoes/{$c->avaliacao->id}/notas", $notas)->assertOk();

    expect(AuditoriaNota::query()->count())->toBe(1);
});

it('registros de auditoria são imutáveis', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $this->putJson("/api/v1/avaliacoes/{$c->avaliacao->id}/notas", ['notas' => [['matricula_id' => $c->matriculas[0]->id, 'valor' => 8]]]);

    $registro = AuditoriaNota::query()->sole();
    expect(fn () => $registro->update(['valor_novo' => 10]))->toThrow(LogicException::class)
        ->and(fn () => $registro->delete())->toThrow(LogicException::class);
});

it('consulta da auditoria é restrita à escola do gestor', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $this->putJson("/api/v1/avaliacoes/{$c->avaliacao->id}/notas", ['notas' => [['matricula_id' => $c->matriculas[0]->id, 'valor' => 8]]]);

    comoApi($c->gestor);
    $this->getJson('/api/v1/auditoria/notas')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.valor_novo', 8);

    comoApi(User::factory()->gestor()->create());
    $this->getJson('/api/v1/auditoria/notas')->assertOk()->assertJsonCount(0, 'data');

    comoApi($c->professor);
    $this->getJson('/api/v1/auditoria/notas')->assertForbidden();
});
