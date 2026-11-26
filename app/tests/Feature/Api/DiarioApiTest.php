<?php

use App\Infrastructure\Persistence\Models\Aula;
use Illuminate\Support\Str;
use Tests\Support\Cenario;

it('registra, consulta e altera aula com frequência', function () {
    $c = Cenario::criar(2);
    comoApi($c->professor);
    $id = (string) Str::uuid();

    $this->postJson("/api/v1/alocacoes/{$c->alocacao->id}/aulas", ['id' => $id, 'data' => '2026-11-20', 'conteudo' => 'Decimais'])
        ->assertCreated()->assertJsonPath('data.id', $id)->assertJsonPath('data.versao', 1);

    $this->putJson("/api/v1/aulas/{$id}/frequencia", ['registros' => [
        ['matricula_id' => $c->matriculas[0]->id, 'presente' => true],
        ['matricula_id' => $c->matriculas[1]->id, 'presente' => false],
    ]])->assertOk()->assertJsonCount(2, 'data.frequencias');

    $this->putJson("/api/v1/aulas/{$id}", ['data' => '2026-11-20', 'conteudo' => 'Decimais e frações', 'versao' => 1])
        ->assertOk()->assertJsonPath('data.versao', 2)->assertJsonPath('data.conteudo', 'Decimais e frações');

    $this->getJson("/api/v1/alocacoes/{$c->alocacao->id}/aulas")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/aulas/{$id}")->assertOk()->assertJsonPath('data.frequencias.1.presente', false);
});

it('valida os dados da aula', function () {
    $c = Cenario::criar();
    comoApi($c->professor);

    $this->postJson("/api/v1/alocacoes/{$c->alocacao->id}/aulas", ['data' => '20/11/2026', 'conteudo' => ''])
        ->assertUnprocessable()->assertJsonValidationErrors(['data', 'conteudo']);
});

it('rejeita frequência de aluno de outra turma', function () {
    $c = Cenario::criar();
    $outra = Cenario::criar();
    comoApi($c->professor);
    $aula = (string) Str::uuid();
    $this->postJson("/api/v1/alocacoes/{$c->alocacao->id}/aulas", ['id' => $aula, 'data' => '2026-11-20', 'conteudo' => 'X']);

    $this->putJson("/api/v1/aulas/{$aula}/frequencia", ['registros' => [['matricula_id' => $outra->matriculas[0]->id, 'presente' => true]]])
        ->assertUnprocessable();
});

it('cria avaliação e lança notas; versão defasada responde 409 sem sobrescrever', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $m = $c->matriculas[0]->id;

    $av = $this->postJson("/api/v1/alocacoes/{$c->alocacao->id}/avaliacoes", ['titulo' => 'Trabalho', 'data' => '2026-11-21', 'periodo' => 2, 'peso' => 2])
        ->assertCreated()->json('data.id');

    $this->putJson("/api/v1/avaliacoes/{$av}/notas", ['notas' => [['matricula_id' => $m, 'valor' => 7]]])->assertOk();
    $this->putJson("/api/v1/avaliacoes/{$av}/notas", ['notas' => [['matricula_id' => $m, 'valor' => 8, 'versao' => 1]]])->assertOk();
    $this->putJson("/api/v1/avaliacoes/{$av}/notas", ['notas' => [['matricula_id' => $m, 'valor' => 3, 'versao' => 1]]])
        ->assertStatus(409)->assertJsonPath('resultados.0.status', 'conflito')->assertJsonPath('resultados.0.dados.valor_servidor', 8);

    $this->getJson("/api/v1/avaliacoes/{$av}/notas")->assertOk()->assertJsonPath('data.notas.0.valor', 8);
});

it('calcula o boletim com média ponderada, frequência e situação', function () {
    $c = Cenario::criar(2);
    comoApi($c->professor);
    [$m1, $m2] = [$c->matriculas[0]->id, $c->matriculas[1]->id];

    $prova2 = $this->postJson("/api/v1/alocacoes/{$c->alocacao->id}/avaliacoes", ['titulo' => 'Prova 2', 'data' => '2026-11-21', 'periodo' => 1, 'peso' => 2])->json('data.id');
    $this->putJson("/api/v1/avaliacoes/{$c->avaliacao->id}/notas", ['notas' => [['matricula_id' => $m1, 'valor' => 9], ['matricula_id' => $m2, 'valor' => 4]]]);
    $this->putJson("/api/v1/avaliacoes/{$prova2}/notas", ['notas' => [['matricula_id' => $m1, 'valor' => 6], ['matricula_id' => $m2, 'valor' => 5]]]);

    // 4 aulas: m1 presente em 4 (100%), m2 presente em 2 (50%)
    foreach (range(1, 4) as $i) {
        $aula = (string) Str::uuid();
        $this->postJson("/api/v1/alocacoes/{$c->alocacao->id}/aulas", ['id' => $aula, 'data' => "2026-11-0{$i}", 'conteudo' => "Aula {$i}"]);
        $this->putJson("/api/v1/aulas/{$aula}/frequencia", ['registros' => [
            ['matricula_id' => $m1, 'presente' => true], ['matricula_id' => $m2, 'presente' => $i <= 2],
        ]]);
    }

    $alunos = collect($this->getJson("/api/v1/alocacoes/{$c->alocacao->id}/boletim")->assertOk()->json('alunos'))->keyBy('matricula_id');

    // m1: (9×1 + 6×2)/3 = 7,0 ; m2: (4×1 + 5×2)/3 = 4,67 -> 4,7
    expect($alunos[$m1])->toMatchArray(['media' => 7.0, 'frequencia' => 100.0, 'faltas' => 0, 'situacao' => 'aprovado'])
        ->and($alunos[$m2])->toMatchArray(['media' => 4.7, 'frequencia' => 50.0, 'faltas' => 2, 'situacao' => 'reprovado_frequencia'])
        ->and(Aula::query()->count())->toBe(4);
});

it('lista escolas, disciplinas e alunos da turma com paginação e recursos', function () {
    $c = Cenario::criar(3);

    comoApi($c->gestor);
    $this->getJson('/api/v1/escolas')->assertOk()->assertJsonStructure(['data' => [['id', 'nome', 'municipio']], 'links', 'meta']);
    $this->getJson('/api/v1/disciplinas')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/turmas/{$c->turma->id}/alunos")->assertOk()->assertJsonCount(3, 'data')->assertJsonStructure(['data' => [['matricula_id', 'nome', 'numero_chamada']]]);
});

it('o snapshot offline traz versões para detecção de conflitos', function () {
    $c = Cenario::criar(1);
    comoApi($c->professor);
    $this->putJson("/api/v1/avaliacoes/{$c->avaliacao->id}/notas", ['notas' => [['matricula_id' => $c->matriculas[0]->id, 'valor' => 7]]]);

    $this->getJson('/api/v1/professor/dados')->assertOk()
        ->assertJsonPath('alocacoes.0.avaliacoes.0.notas.0.versao', 1)
        ->assertJsonStructure(['usuario', 'gerado_em', 'criterios', 'alocacoes' => [['turma', 'disciplina', 'alunos', 'aulas', 'avaliacoes']]]);
});
