<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->name,
            'email' => $this->email,
            'papel' => $this->papel->value,
            'papel_rotulo' => $this->papel->rotulo(),
            'escola_id' => $this->escola_id,
            'escola' => $this->whenLoaded('escola', fn () => $this->escola?->nome),
            'ativo' => $this->ativo,
        ];
    }
}
