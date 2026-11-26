<?php

use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\Frequencia;
use Illuminate\Support\Str;
use Tests\Support\Cenario;

function registrarAula(Cenario $c, string $aulaId, string $conteudo, int $versaoBase, string $ocorridoEm): array
{
    return test()->postJson('/api/v1/sync', ['operacoes' => [
        operacao('aula.registrar', ['aula_id' => $aulaId, 'alocacao_id' => $c->alocacao->id, 'data' => '2026-11-20', 'conteudo' => $conteudo], $versaoBase, $ocorridoEm),
    ]])->assertOk()->json('resultados.0');
}

it('aula: edição concorrente mais recente vence (last-write-wins)', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $id = (string) Str::uuid();

    registrarAula($c, $id, 'Versão inicial', 0, '2026-11-20T08:00:00-03:00');           // v1
    registrarAula($c, $id, 'Editada no notebook', 1, '2026-11-20T09:00:00-03:00');      // v2
    // Celular offline editou a partir da v1 às 10h (mais recente): vence
    $r = registrarAula($c, $id, 'Editada no celular offline', 1, '2026-11-20T10:00:00-03:00');

    expect($r['status'])->toBe('aplicada')
        ->and(Aula::query()->find($id))->conteudo->toBe('Editada no celular offline')->versao->toBe(3);
});

it('aula: edição concorrente mais antiga é descartada (last-write-wins)', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $id = (string) Str::uuid();

    registrarAula($c, $id, 'Versão inicial', 0, '2026-11-20T08:00:00-03:00');
    registrarAula($c, $id, 'Editada às 11h', 1, '2026-11-20T11:00:00-03:00');
    $r = registrarAula($c, $id, 'Editada offline às 10h', 1, '2026-11-20T10:00:00-03:00');

    expect($r['status'])->toBe('descartada')
        ->and(Aula::query()->find($id))->conteudo->toBe('Editada às 11h')->versao->toBe(2);
});

it('frequência: LWW por aluno', function () {
    $c = Cenario::criar(2);
    comoApi($c->professor);
    $id = (string) Str::uuid();
    [$m1, $m2] = [$c->matriculas[0]->id, $c->matriculas[1]->id];
    registrarAula($c, $id, 'Aula', 0, '2026-11-20T08:00:00-03:00');

    $freq = fn (array $registros, string $em) => $this->postJson('/api/v1/sync', ['operacoes' => [
        operacao('frequencia.registrar', ['aula_id' => $id, 'registros' => $registros], 0, $em),
    ]])->assertOk()->json('resultados.0');

    $freq([['matricula_id' => $m1, 'presente' => true], ['matricula_id' => $m2, 'presente' => true]], '2026-11-20T08:10:00-03:00');
    $freq([['matricula_id' => $m1, 'presente' => false, 'versao_base' => 1]], '2026-11-20T12:00:00-03:00'); // m1 -> falta (v2)

    // Dispositivo antigo (base v1, 09h) tenta marcar m1 presente e m2 falta
    $r = $freq([
        ['matricula_id' => $m1, 'presente' => true, 'versao_base' => 1],
        ['matricula_id' => $m2, 'presente' => false, 'versao_base' => 1],
    ], '2026-11-20T09:00:00-03:00');

    expect($r['status'])->toBe('aplicada')
        ->and($r['dados']['descartados'])->toBe(1)
        ->and(Frequencia::query()->where('matricula_id', $m1)->sole()->presente)->toBeFalse()   // mais recente (12h) mantida
        ->and(Frequencia::query()->where('matricula_id', $m2)->sole()->presente)->toBeFalse();  // sem concorrência: aplicada
});

it('limita ocorrido_em ao relógio do servidor (relógio do dispositivo adiantado)', function () {
    $c = Cenario::criar();
    comoApi($c->professor);
    $id = (string) Str::uuid();

    registrarAula($c, $id, 'Do futuro', 0, now()->addYear()->toIso8601String());

    expect(Aula::query()->find($id)->alterado_em->lessThanOrEqualTo(now()->addMinute()))->toBeTrue();
});
