<?php

namespace Database\Seeders;

use App\Application\Avaliacao\LancarNota;
use App\Application\Avaliacao\LancarNotaComando;
use App\Application\Diario\RegistrarAula;
use App\Application\Diario\RegistrarAulaComando;
use App\Application\Diario\RegistrarFrequencia;
use App\Application\Diario\RegistrarFrequenciaComando;
use App\Application\Shared\ContextoUsuario;
use App\Domain\Avaliacao\OrigemAlteracao;
use App\Domain\Shared\Papel;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\Avaliacao;
use App\Infrastructure\Persistence\Models\Disciplina;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Matricula;
use App\Infrastructure\Persistence\Models\Municipio;
use App\Infrastructure\Persistence\Models\Turma;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Dados de demonstração (fictícios): 1 município, 2 escolas, 4 turmas,
 * 48 alunos e um usuário por papel. Senha de todos: DemoSeeder::SENHA.
 */
class DemoSeeder extends Seeder
{
    public const string SENHA = 'dem@2026';

    public function run(RegistrarAula $registrarAula, RegistrarFrequencia $registrarFrequencia, LancarNota $lancarNota): void
    {
        // Gerador determinístico próprio (sem Faker, que é dependência de dev e
        // não vai na imagem de produção): os mesmos dados em toda execução
        $nomes = ['Alice', 'Arthur', 'Beatriz', 'Bernardo', 'Bruna', 'Caio', 'Camila', 'Davi', 'Eduarda', 'Enzo',
            'Fernanda', 'Felipe', 'Gabriela', 'Gustavo', 'Heloísa', 'Heitor', 'Isabela', 'Igor', 'Júlia', 'João',
            'Larissa', 'Lucas', 'Manuela', 'Mateus', 'Natália', 'Nicolas', 'Olívia', 'Otávio', 'Pietra', 'Pedro',
            'Rafaela', 'Rodrigo', 'Sofia', 'Samuel', 'Talita', 'Thiago', 'Valentina', 'Vinícius', 'Yasmin', 'Yuri',
            'Lívia', 'Leonardo', 'Mariana', 'Miguel', 'Lorena', 'Henrique', 'Cecília', 'Benício'];
        $sobrenomes = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Pereira', 'Lima', 'Carvalho', 'Ferreira', 'Rodrigues',
            'Almeida', 'Costa', 'Gomes', 'Martins', 'Araújo', 'Ribeiro', 'Barbosa', 'Rocha', 'Dias', 'Moreira', 'Cardoso',
            'Teixeira', 'Correia', 'Nunes', 'Mendes', 'Pinto', 'Vieira', 'Schmitt', 'Weber', 'Becker', 'Fontana'];
        $adultos = ['Adriana', 'Cláudio', 'Daniela', 'Fábio', 'Luciana', 'Marcelo', 'Patrícia', 'Ricardo', 'Simone', 'Sérgio'];
        $sequencialFuncional = 1000;
        $sequencialAluno = 0;
        $ano = (int) now()->format('Y');

        $municipio = Municipio::query()->create(['nome' => 'Vale Verde', 'uf' => 'SC']);

        $escola1 = Escola::query()->create([
            'municipio_id' => $municipio->id, 'nome' => 'EMEF Ipê Amarelo', 'codigo_inep' => '42000001',
            'endereco' => 'Rua das Flores, 100 — Centro', 'telefone' => '(49) 3300-0001',
        ]);
        $escola2 = Escola::query()->create([
            'municipio_id' => $municipio->id, 'nome' => 'EMEF Rio Bonito', 'codigo_inep' => '42000002',
            'endereco' => 'Linha Rio Bonito, s/n — Interior', 'telefone' => '(49) 3300-0002',
        ]);

        $disciplinas = collect([
            ['LP', 'Língua Portuguesa'], ['MAT', 'Matemática'], ['CIE', 'Ciências'], ['HIS', 'História'], ['GEO', 'Geografia'],
        ])->mapWithKeys(fn (array $d) => [$d[0] => Disciplina::query()->create(['codigo' => $d[0], 'nome' => $d[1], 'carga_horaria' => 160])]);

        $usuario = fn (string $nome, string $email, Papel $papel, ?Escola $escola) => User::query()->create([
            'name' => $nome, 'email' => $email, 'password' => self::SENHA, 'papel' => $papel,
            'escola_id' => $escola?->id, 'email_verified_at' => now(),
            'matricula_funcional' => $papel === Papel::Admin ? null : 'MF-'.(++$sequencialFuncional),
        ]);

        $usuario('Ana Souza (Secretaria)', 'admin@dem.local', Papel::Admin, null);
        $usuario('Carlos Lima (Gestor)', 'gestor@dem.local', Papel::Gestor, $escola1);
        $usuario('Beatriz Rocha (Gestora)', 'gestor2@dem.local', Papel::Gestor, $escola2);
        $professor = $usuario('Paula Mendes (Professora)', 'professor@dem.local', Papel::Professor, $escola1);
        $professor2 = $usuario('Rafael Costa (Professor)', 'professor2@dem.local', Papel::Professor, $escola1);
        $professor3 = $usuario('Juliana Alves (Professora)', 'professor3@dem.local', Papel::Professor, $escola2);

        $turmas = [
            [$escola1, '5º ano A', '5º ano', 'matutino'],
            [$escola1, '6º ano A', '6º ano', 'vespertino'],
            [$escola2, '4º ano A', '4º ano', 'matutino'],
            [$escola2, '5º ano B', '5º ano', 'matutino'],
        ];

        /** @var array<string, Turma> $criadas */
        $criadas = [];
        foreach ($turmas as [$escola, $nome, $serie, $turno]) {
            $turma = Turma::query()->create(['escola_id' => $escola->id, 'nome' => $nome, 'serie' => $serie, 'turno' => $turno, 'ano_letivo' => $ano]);
            $alunos = collect(range(1, 12))->map(function () use (&$sequencialAluno, $nomes, $sobrenomes, $adultos, $ano) {
                $n = $sequencialAluno++;
                $familia = $sobrenomes[($n * 7) % count($sobrenomes)];

                return [
                    'nome' => $nomes[$n].' '.$sobrenomes[($n * 11 + 3) % count($sobrenomes)].' '.$familia,
                    'codigo' => 'A'.(4200000 + $n * 137),
                    'data_nascimento' => Carbon::create($ano - 12, 3, 1)->addDays(($n * 53) % 1000)->format('Y-m-d'),
                    'responsavel' => $adultos[$n % count($adultos)].' '.$familia,
                ];
            })->sortBy('nome')->values();
            foreach ($alunos as $i => $dadosAluno) {
                $aluno = Aluno::query()->create(['escola_id' => $escola->id, ...$dadosAluno]);
                Matricula::query()->create([
                    'aluno_id' => $aluno->id, 'turma_id' => $turma->id, 'numero_chamada' => $i + 1,
                    'situacao' => 'ativa', 'data_matricula' => "{$ano}-02-10",
                ]);
            }
            $criadas[$nome] = $turma;
        }

        $alocar = fn (Turma $t, string $disc, User $p) => Alocacao::query()->create([
            'turma_id' => $t->id, 'disciplina_id' => $disciplinas[$disc]->id, 'professor_id' => $p->id,
        ]);

        $mat5a = $alocar($criadas['5º ano A'], 'MAT', $professor);
        $alocar($criadas['5º ano A'], 'CIE', $professor);
        $alocar($criadas['6º ano A'], 'MAT', $professor);
        $alocar($criadas['5º ano A'], 'LP', $professor2);
        $alocar($criadas['6º ano A'], 'LP', $professor2);
        $alocar($criadas['4º ano A'], 'LP', $professor3);
        $alocar($criadas['5º ano B'], 'MAT', $professor3);

        // Diário de exemplo da professora demo (5º ano A · Matemática), lançado pelos casos de uso
        $ctx = new ContextoUsuario($professor->id, Papel::Professor, $escola1->id);
        $matriculas = Matricula::query()->where('turma_id', $criadas['5º ano A']->id)->orderBy('numero_chamada')->get();
        $conteudos = ['Frações: conceito e representação', 'Frações equivalentes', 'Comparação de frações'];

        foreach ($conteudos as $i => $conteudo) {
            $data = Carbon::now()->subDays(7 - $i * 2)->startOfDay();
            $aulaId = (string) Str::uuid();
            $registrarAula->executar($ctx, new RegistrarAulaComando($aulaId, $mat5a->id, $data->toDateTimeImmutable(), $conteudo, null, 0, $data->copy()->setTime(9, 0)->toDateTimeImmutable()));
            /** @var list<array{matricula_id: int, presente: bool, versao_base: int}> $registros */
            $registros = $matriculas->values()->map(fn (Matricula $m, int $k) => [
                'matricula_id' => $m->id, 'presente' => ($k + $i) % 7 !== 0, 'versao_base' => 0,
            ])->all();
            $registrarFrequencia->executar($ctx, new RegistrarFrequenciaComando(
                $aulaId,
                $registros,
                $data->copy()->setTime(9, 5)->toDateTimeImmutable(),
            ));
        }

        $prova = Avaliacao::query()->create(['alocacao_id' => $mat5a->id, 'titulo' => 'Prova 1 — Frações', 'data' => now()->subDays(2)->toDateString(), 'periodo' => 1, 'peso' => 2]);
        Avaliacao::query()->create(['alocacao_id' => $mat5a->id, 'titulo' => 'Trabalho em grupo', 'data' => now()->addDays(3)->toDateString(), 'periodo' => 1, 'peso' => 1]);

        foreach ($matriculas as $k => $m) {
            $lancarNota->executar($ctx, new LancarNotaComando(
                $prova->id, $m->id, round(4 + ($k * 0.55) % 6, 1), 0, now()->subDay()->toDateTimeImmutable(), OrigemAlteracao::Api,
            ));
        }
    }
}
