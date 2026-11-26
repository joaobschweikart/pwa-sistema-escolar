<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Escola>
 */
class EscolaFactory extends Factory
{
    protected $model = Escola::class;

    public function definition(): array
    {
        return [
            'municipio_id' => fn () => Municipio::query()->firstOrCreate(['nome' => 'Município Exemplo', 'uf' => 'SC'])->id,
            'nome' => 'Escola Municipal '.fake()->unique()->lastName(),
            'codigo_inep' => fake()->unique()->numerify('42######'),
            'endereco' => fake()->streetAddress(),
            'telefone' => fake()->numerify('(49) 3###-####'),
        ];
    }
}
