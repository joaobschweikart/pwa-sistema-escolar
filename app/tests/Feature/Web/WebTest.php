<?php

use App\Infrastructure\Notifications\RedefinirSenhaNotification;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Support\Cenario;

it('faz login e redireciona conforme o papel', function () {
    $c = Cenario::criar();

    $this->post('/login', ['email' => $c->professor->email, 'password' => 'password'])->assertRedirect('/painel');
    $this->get('/painel')->assertRedirect('/professor');
    $this->get('/professor')->assertOk()->assertHeader('X-DEM-Shell', '1')->assertSee('diarioProfessor', false);
});

it('recusa login inválido e de usuário inativo', function () {
    $u = User::factory()->create(['ativo' => false]);

    $this->post('/login', ['email' => $u->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('emite token da PWA só para professor e revoga no logout', function () {
    $c = Cenario::criar();

    $this->actingAs($c->professor)->postJson('/pwa/token')->assertOk()->assertJsonStructure(['token', 'expira_em']);
    expect($c->professor->tokens()->count())->toBe(1);

    $this->post('/logout')->assertRedirect('/login');
    expect($c->professor->tokens()->count())->toBe(0);
    $this->assertGuest();

    $this->actingAs($c->gestor)->postJson('/pwa/token')->assertForbidden();
});

it('envia o link de recuperação de senha e redefine a senha', function () {
    Notification::fake();
    $u = User::factory()->create();

    $this->post('/esqueci-senha', ['email' => $u->email])->assertSessionHas('status');
    Notification::assertSentTo($u, RedefinirSenhaNotification::class);

    $token = Password::createToken($u);
    $this->post('/redefinir-senha', ['token' => $token, 'email' => $u->email, 'password' => 'novaSenha123', 'password_confirmation' => 'novaSenha123'])
        ->assertRedirect('/login');
    $this->post('/login', ['email' => $u->email, 'password' => 'novaSenha123'])->assertRedirect('/painel');
});

it('resposta da recuperação de senha não revela se o e-mail existe', function () {
    Notification::fake();
    $this->post('/esqueci-senha', ['email' => 'ninguem@exemplo.com'])->assertSessionHas('status');
    Notification::assertNothingSent();
});

it('renderiza as telas de gestão para o admin', function (string $url) {
    Cenario::criar();
    $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
})->with(['/painel', '/gestao/escolas', '/gestao/escolas/create', '/gestao/disciplinas', '/gestao/usuarios', '/gestao/turmas', '/gestao/alunos', '/gestao/conflitos', '/gestao/auditoria']);

it('gestor gerencia turma, matrícula e vínculo da própria escola', function () {
    $c = Cenario::criar(1);
    $this->actingAs($c->gestor);

    $this->get("/gestao/turmas/{$c->turma->id}")->assertOk()->assertSee($c->turma->nome);
    $this->get("/gestao/diario/{$c->alocacao->id}")->assertOk()->assertSee('Boletim');
    $this->post('/gestao/turmas', ['nome' => '9º ano C', 'serie' => '9º ano', 'turno' => 'vespertino', 'ano_letivo' => 2026])->assertRedirect();
    $this->assertDatabaseHas('turmas', ['nome' => '9º ano C', 'escola_id' => $c->escola->id]);

    $this->get('/gestao/escolas')->assertForbidden();
    $this->get('/gestao/disciplinas')->assertForbidden();
});

it('gestor só cadastra professores da própria escola', function () {
    $c = Cenario::criar();
    $outra = Cenario::criar();

    $this->actingAs($c->gestor)->post('/gestao/usuarios', [
        'name' => 'Novo Prof', 'email' => 'novo@dem.local', 'papel' => 'admin', 'password' => 'senha1234', 'ativo' => 1,
    ])->assertSessionHasErrors('papel');

    $this->post('/gestao/usuarios', [
        'name' => 'Novo Prof', 'email' => 'novo@dem.local', 'papel' => 'professor', 'escola_id' => $outra->escola->id, 'password' => 'senha1234', 'ativo' => 1,
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'novo@dem.local', 'escola_id' => $c->escola->id]);
    $this->get("/gestao/usuarios/{$outra->professor->id}/edit")->assertForbidden();
});
