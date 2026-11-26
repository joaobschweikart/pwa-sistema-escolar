<?php

namespace Tests\Support;

use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\Avaliacao;
use App\Infrastructure\Persistence\Models\Disciplina;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Matricula;
use App\Infrastructure\Persistence\Models\Turma;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Collection;

/**
 * Monta uma escola com turma, alunos matriculados, professor vinculado e avaliação.
 */
final class Cenario
{
    public Escola $escola;

    public Turma $turma;

    public User $professor;

    public User $gestor;

    public Alocacao $alocacao;

    public Avaliacao $avaliacao;

    /** @var Collection<int, Matricula> */
    public Collection $matriculas;

    public static function criar(int $alunos = 3, ?Escola $escola = null): self
    {
        $c = new self;
        $c->escola = $escola ?? Escola::factory()->create();
        $c->turma = Turma::factory()->create(['escola_id' => $c->escola->id]);
        $c->professor = User::factory()->professor($c->escola)->create();
        $c->gestor = User::factory()->gestor($c->escola)->create();
        $c->alocacao = Alocacao::query()->create([
            'turma_id' => $c->turma->id,
            'disciplina_id' => Disciplina::factory()->create()->id,
            'professor_id' => $c->professor->id,
        ]);
        $c->matriculas = collect(range(1, $alunos))->map(fn (int $i) => Matricula::query()->create([
            'aluno_id' => Aluno::factory()->create(['escola_id' => $c->escola->id])->id,
            'turma_id' => $c->turma->id,
            'numero_chamada' => $i,
            'situacao' => 'ativa',
            'data_matricula' => now()->toDateString(),
        ]));
        $c->avaliacao = Avaliacao::query()->create([
            'alocacao_id' => $c->alocacao->id, 'titulo' => 'Prova 1', 'data' => now()->toDateString(), 'periodo' => 1, 'peso' => 1,
        ]);

        return $c;
    }
}
