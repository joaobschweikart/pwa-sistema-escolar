<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Papel;
use App\Infrastructure\Notifications\RedefinirSenhaNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Papel $papel
 * @property int|null $escola_id
 * @property string|null $matricula_funcional
 * @property bool $ativo
 * @property-read Escola|null $escola
 */
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'papel', 'escola_id', 'matricula_funcional', 'ativo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'papel' => Papel::class,
            'ativo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Escola, $this> */
    public function escola(): BelongsTo
    {
        return $this->belongsTo(Escola::class);
    }

    /** @return HasMany<Alocacao, $this> */
    public function alocacoes(): HasMany
    {
        return $this->hasMany(Alocacao::class, 'professor_id');
    }

    public function ehAdmin(): bool
    {
        return $this->papel === Papel::Admin;
    }

    public function ehGestor(): bool
    {
        return $this->papel === Papel::Gestor;
    }

    public function ehProfessor(): bool
    {
        return $this->papel === Papel::Professor;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeVisiveisPara(Builder $query, User $usuario): Builder
    {
        return $usuario->ehAdmin() ? $query : $query->where('escola_id', $usuario->escola_id);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new RedefinirSenhaNotification($token));
    }
}
