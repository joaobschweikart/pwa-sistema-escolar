<?php

use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\Nota;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Str;
use Tests\Support\Cenario;

it('professor NÃO acessa turma de outro professor (API)', function () {
    $minha = Cenario::criar();
    $alheia = Cenario::criar(escola: $minha->escola);
    comoApi($minha->professor);

    $this->getJson("/api/v1/turmas/{$alheia->turma->id}")->assertForbidden();
    $this->getJson("/api/v1/turmas/{$alheia->turma->id}/alunos")->assertForbidden();
    $this->getJson("/api/v1/alocacoes/{$alheia->alocacao->id}")->assertForbidden();
    $this->getJson("/api/v1/alocacoes/{$alheia->alocacao->id}/aulas")->assertForbidden();
    $this->getJson("/api/v1/alocacoes/{$alheia->alocacao->id}/boletim")->assertForbidden();
    $this->getJson("/api/v1/avaliacoes/{$alheia->avaliacao->id}/notas")->assertForbidden();
    $this->postJson("/api/v1/alocacoes/{$alheia->alocacao->id}/aulas", ['data' => '2026-11-20', 'conteudo' => 'X'])->assertForbidden();
    $this->postJson("/api/v1/alocacoes/{$alheia->alocacao->id}/avaliacoes", ['titulo' => 'X', 'data' => '2026-11-20', 'periodo' => 1, 'peso' => 1])->assertForbidden();
    $this->putJson("/api/v1/avaliacoes/{$alheia->avaliacao->id}/notas", ['notas' => [['matricula_id' => $alheia->matriculas[0]->id, 'valor' => 10]]])->assertForbidden();

    $this->getJson("/api/v1/turmas/{$minha->turma->id}")->assertOk();
    expect(Nota::query()->count())->toBe(0);
});

it('professor NÃO lança diário em turma alheia via sincronização', function () {
    $minha = Cenario::criar();
    $alheia = Cenario::criar();
    comoApi($minha->professor);

    $this->postJson('/api/v1/sync', ['operacoes' => [
        operacao('aula.registrar', ['aula_id' => (string) Str::uuid(), 'alocacao_id' => $alheia->alocacao->id, 'data' => '2026-11-20', 'conteudo' => 'Invasão']),
        operacao('nota.lancar', ['avaliacao_id' => $alheia->avaliacao->id, 'matricula_id' => $alheia->matriculas[0]->id, 'valor' => 10]),
        operacao('nota.lancar', ['avaliacao_id' => $minha->avaliacao->id, 'matricula_id' => $alheia->matriculas[0]->id, 'valor' => 10]),
    ]])
        ->assertOk()
        ->assertJsonPath('resultados.0.status', 'rejeitada')
        ->assertJsonPath('resultados.1.status', 'rejeitada')
        ->assertJsonPath('resultados.2.status', 'rejeitada')
        ->assertJsonPath('resumo.rejeitadas', 3);

    expect(Aula::query()->count())->toBe(0)->and(Nota::query()->count())->toBe(0);
});

it('listas mostram ao professor somente as próprias turmas', function () {
    $minha = Cenario::criar();
    Cenario::criar(escola: $minha->escola);
    comoApi($minha->professor);

    $this->getJson('/api/v1/turmas')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $minha->turma->id);
    $this->getJson('/api/v1/alocacoes')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/professor/dados')->assertOk()->assertJsonCount(1, 'alocacoes')->assertJsonPath('alocacoes.0.id', $minha->alocacao->id);
});

it('gestor vê só a própria escola; admin vê a rede', function () {
    $a = Cenario::criar();
    $b = Cenario::criar();

    comoApi($a->gestor);
    $this->getJson('/api/v1/escolas')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/escolas/{$b->escola->id}")->assertForbidden();
    $this->getJson("/api/v1/turmas/{$b->turma->id}")->assertForbidden();
    $this->getJson("/api/v1/alocacoes/{$a->alocacao->id}/boletim")->assertOk();

    comoApi(User::factory()->admin()->create());
    $this->getJson('/api/v1/escolas')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson("/api/v1/turmas/{$b->turma->id}")->assertOk();
});

it('gestor e admin não registram diário (somente o professor vinculado)', function () {
    $c = Cenario::criar();
    comoApi($c->gestor);

    $this->postJson("/api/v1/alocacoes/{$c->alocacao->id}/aulas", ['data' => '2026-11-20', 'conteudo' => 'X'])->assertForbidden();
    $this->getJson('/api/v1/professor/dados')->assertForbidden();
});

it('somente admin cadastra escolas', function () {
    $c = Cenario::criar();
    $dados = ['municipio_id' => $c->escola->municipio_id, 'nome' => 'EMEF Nova'];

    comoApi($c->gestor);
    $this->postJson('/api/v1/escolas', $dados)->assertForbidden();

    comoApi(User::factory()->admin()->create());
    $this->postJson('/api/v1/escolas', $dados)->assertCreated()->assertJsonPath('data.nome', 'EMEF Nova');
});

it('professor NÃO acessa turma alheia nem telas de gestão (web)', function () {
    $minha = Cenario::criar();
    $alheia = Cenario::criar(escola: $minha->escola);

    $this->actingAs($minha->professor)->get("/gestao/turmas/{$alheia->turma->id}")->assertForbidden();
    $this->get("/gestao/diario/{$alheia->alocacao->id}")->assertForbidden();
    $this->get('/gestao/conflitos')->assertForbidden();
});
