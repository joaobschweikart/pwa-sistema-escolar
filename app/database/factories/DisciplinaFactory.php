<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\Disciplina;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disciplina>
 */
class DisciplinaFactory extends Factory
{
    protected $model = Disciplina::class;

    public function definition(): array
    {
        return [
            'nome' => ucfirst(fake()->unique()->word()),
            'codigo' => strtoupper(fake()->unique()->bothify('DSC-###')),
            'carga_horaria' => 80,
        ];
    }
}
