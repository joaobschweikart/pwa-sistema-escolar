<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class NotasRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'notas' => ['required', 'array', 'min:1', 'max:100'],
            'notas.*.matricula_id' => ['required', 'integer', 'distinct'],
            'notas.*.valor' => ['required', 'numeric', 'between:0,10'],
            'notas.*.versao' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
