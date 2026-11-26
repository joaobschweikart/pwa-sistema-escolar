<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class FrequenciaRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'registros' => ['required', 'array', 'min:1', 'max:100'],
            'registros.*.matricula_id' => ['required', 'integer', 'distinct'],
            'registros.*.presente' => ['required', 'boolean'],
            'registros.*.versao' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
