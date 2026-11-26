<?php

namespace Database\Factories;

use App\Domain\Shared\Papel;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'papel' => Papel::Professor,
            'ativo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['papel' => Papel::Admin, 'escola_id' => null]);
    }

    public function gestor(?Escola $escola = null): static
    {
        return $this->state(fn () => ['papel' => Papel::Gestor, 'escola_id' => $escola->id ?? Escola::factory()]);
    }

    public function professor(?Escola $escola = null): static
    {
        return $this->state(fn () => ['papel' => Papel::Professor, 'escola_id' => $escola->id ?? Escola::factory()]);
    }
}
