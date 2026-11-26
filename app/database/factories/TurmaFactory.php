<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Turma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turma>
 */
class TurmaFactory extends Factory
{
    protected $model = Turma::class;

    public function definition(): array
    {
        $serie = fake()->numberBetween(1, 9);

        return [
            'escola_id' => Escola::factory(),
            'nome' => "{$serie}º ano ".fake()->unique()->bothify('?#'),
            'serie' => "{$serie}º ano",
            'turno' => 'matutino',
            'ano_letivo' => (int) date('Y'),
        ];
    }
}
