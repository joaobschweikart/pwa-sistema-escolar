<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Sincronizacao\ResolucaoConflito;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolverConflitoRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'resolucao' => ['required', Rule::enum(ResolucaoConflito::class)],
        ];
    }
}
