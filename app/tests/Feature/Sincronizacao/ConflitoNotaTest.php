<?php

use App\Infrastructure\Persistence\Models\AuditoriaNota;
use App\Infrastructure\Persistence\Models\ConflitoNota;
use App\Infrastructure\Persistence\Models\Nota;
use App\Infrastructure\Persistence\Models\User;
use Tests\Support\Cenario;

function lancar(Cenario $c, float $valor, int $versaoBase, int $indice = 0): array
{
    return test()->postJson('/api/v1/sync', ['operacoes' => [
        operacao('nota.lancar', ['avaliacao_id' => $c->avaliacao->id, 'matricula_id' => $c->matriculas[$indice]->id, 'valor' => $valor], $versaoBase),
    ]])->assertOk()->json('resultados.0');
}

it('conflito de nota NÃO é aplicado e vai para a fila do gestor', function () {
    $c = Cenario::criar();
    comoApi($c->professor);

    lancar($c, 6.0, 0);              // v1
    lancar($c, 7.0, 1);              // v2 (ex.: corrigida online)
    $r = lancar($c, 9.0, 1);         // offline a partir da v1 -> conflito

    expect($r['status'])->toBe('conflito')
        ->and($r['dados']['conflito_id'])->toBeInt()
        ->and(Nota::query()->sole())->valor->toBe(7.0)->versao->toBe(2)
        ->and(ConflitoNota::query()->sole())
        ->status->toBe('pendente')
        ->valor_servidor->toBe(7.0)
        ->valor_proposto->toBe(9.0)
        ->versao_base->toBe(1)
        ->versao_servidor->toBe(2);
});

it('não gera conflito quando o valor proposto é igual ao atual', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    lancar($c, 6.0, 0);
    lancar($c, 7.0, 1);

    expect(lancar($c, 7.0, 1)['status'])->toBe('aplicada')
        ->and(ConflitoNota::query()->count())->toBe(0);
});

it('gestor resolve aplicando o valor proposto (com auditoria)', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    lancar($c, 6.0, 0);
    lancar($c, 7.0, 1);
    $conflito = lancar($c, 9.0, 1)['dados']['conflito_id'];

    comoApi($c->gestor);
    $this->postJson("/api/v1/conflitos/{$conflito}/resolver", ['resolucao' => 'aplicar_proposto'])
        ->assertOk()
        ->assertJsonPath('conflito.status', 'resolvido')
        ->assertJsonPath('conflito.resolucao', 'aplicar_proposto');

    $auditoria = AuditoriaNota::query()->latest('id')->first();
    expect(Nota::query()->sole())->valor->toBe(9.0)->versao->toBe(3)
        ->and($auditoria)->origem->toBe('resolucao_conflito')->usuario_id->toBe($c->gestor->id)
        ->valor_anterior->toBe(7.0)->valor_novo->toBe(9.0);
});

it('gestor resolve mantendo o valor atual', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    lancar($c, 6.0, 0);
    lancar($c, 7.0, 1);
    $conflito = lancar($c, 9.0, 1)['dados']['conflito_id'];

    comoApi($c->gestor);
    $this->postJson("/api/v1/conflitos/{$conflito}/resolver", ['resolucao' => 'manter_atual'])->assertOk();

    expect(Nota::query()->sole()->valor)->toBe(7.0)
        ->and(ConflitoNota::query()->sole()->status)->toBe('resolvido')
        ->and(AuditoriaNota::query()->count())->toBe(2);
});

it('conflito resolvido não pode ser resolvido de novo', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    lancar($c, 6.0, 0);
    lancar($c, 7.0, 1);
    $conflito = lancar($c, 9.0, 1)['dados']['conflito_id'];

    comoApi($c->gestor);
    $this->postJson("/api/v1/conflitos/{$conflito}/resolver", ['resolucao' => 'manter_atual'])->assertOk();
    $this->postJson("/api/v1/conflitos/{$conflito}/resolver", ['resolucao' => 'aplicar_proposto'])->assertNotFound();
});

it('gestor de outra escola e professor não resolvem o conflito', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    lancar($c, 6.0, 0);
    lancar($c, 7.0, 1);
    $conflito = lancar($c, 9.0, 1)['dados']['conflito_id'];

    $this->postJson("/api/v1/conflitos/{$conflito}/resolver", ['resolucao' => 'aplicar_proposto'])->assertForbidden();

    comoApi(User::factory()->gestor()->create());
    $this->postJson("/api/v1/conflitos/{$conflito}/resolver", ['resolucao' => 'aplicar_proposto'])->assertForbidden();
    $this->getJson('/api/v1/conflitos')->assertOk()->assertJsonCount(0, 'data');

    expect(Nota::query()->sole()->valor)->toBe(7.0);
});

it('gestor resolve conflito pela tela web', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    lancar($c, 6.0, 0);
    lancar($c, 7.0, 1);
    $conflito = lancar($c, 9.0, 1)['dados']['conflito_id'];

    $this->actingAs($c->gestor)->get('/gestao/conflitos')->assertOk()->assertSee('9,0')->assertSee('7,0');
    $this->post("/gestao/conflitos/{$conflito}/resolver", ['resolucao' => 'aplicar_proposto'])->assertRedirect();

    expect(Nota::query()->sole()->valor)->toBe(9.0);
});
