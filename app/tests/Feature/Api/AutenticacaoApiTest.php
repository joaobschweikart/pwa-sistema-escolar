<?php

use App\Infrastructure\Persistence\Models\User;

it('emite token Bearer com credenciais válidas', function () {
    $u = User::factory()->professor()->create(['password' => 'segredo123']);

    $resposta = $this->postJson('/api/v1/auth/token', ['email' => $u->email, 'password' => 'segredo123', 'dispositivo' => 'teste'])
        ->assertCreated()
        ->assertJsonStructure(['token', 'tipo', 'expira_em', 'usuario' => ['id', 'nome', 'papel']]);

    $this->withToken($resposta->json('token'))->getJson('/api/v1/eu')->assertOk()->assertJsonPath('data.email', $u->email);
});

it('recusa credenciais inválidas e usuário inativo', function () {
    $u = User::factory()->create(['password' => 'segredo123']);
    $inativo = User::factory()->create(['password' => 'segredo123', 'ativo' => false]);

    $this->postJson('/api/v1/auth/token', ['email' => $u->email, 'password' => 'errada', 'dispositivo' => 't'])->assertUnprocessable();
    $this->postJson('/api/v1/auth/token', ['email' => $inativo->email, 'password' => 'segredo123', 'dispositivo' => 't'])->assertUnprocessable();
});

it('revoga o token', function () {
    $u = User::factory()->create(['password' => 'segredo123']);
    $token = $this->postJson('/api/v1/auth/token', ['email' => $u->email, 'password' => 'segredo123', 'dispositivo' => 't'])->json('token');

    $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertNoContent();
    expect($u->tokens()->count())->toBe(0);
});

it('aplica rate limit no login', function () {
    $u = User::factory()->create();
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/v1/auth/token', ['email' => $u->email, 'password' => 'x', 'dispositivo' => 't']);
    }
    $this->postJson('/api/v1/auth/token', ['email' => $u->email, 'password' => 'x', 'dispositivo' => 't'])->assertTooManyRequests();
});

it('healthcheck responde com o estado do banco', function () {
    $this->getJson('/api/health')->assertOk()->assertJson(['status' => 'ok', 'banco' => 'ok']);
});

it('documentação OpenAPI está disponível', function () {
    $this->get('/docs/api.json')->assertOk()->assertJsonPath('info.version', '1.0.0')->assertJsonStructure(['openapi', 'paths' => ['/v1/sync', '/v1/auth/token', '/health']]);
});
