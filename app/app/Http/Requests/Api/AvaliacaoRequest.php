<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AvaliacaoRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'data' => ['required', 'date_format:Y-m-d'],
            'periodo' => ['required', 'integer', 'between:1,4'],
            'peso' => ['required', 'numeric', 'min:0.1', 'max:10'],
        ];
    }
}
