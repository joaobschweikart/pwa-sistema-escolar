<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\Escola;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aluno>
 */
class AlunoFactory extends Factory
{
    protected $model = Aluno::class;

    public function definition(): array
    {
        return [
            'escola_id' => Escola::factory(),
            'nome' => fake()->name(),
            'codigo' => fake()->unique()->numerify('A########'),
            'data_nascimento' => fake()->dateTimeBetween('-15 years', '-6 years')->format('Y-m-d'),
            'responsavel' => fake()->name(),
        ];
    }
}
