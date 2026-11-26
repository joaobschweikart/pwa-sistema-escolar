<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AulaRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $criando = $this->isMethod('post');

        return [
            // UUID pode ser gerado pelo cliente (PWA offline) ou omitido
            'id' => [$criando ? 'nullable' : 'prohibited', 'uuid'],
            'data' => ['required', 'date_format:Y-m-d'],
            'conteudo' => ['required', 'string', 'max:5000'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'versao' => [$criando ? 'nullable' : 'required', 'integer', 'min:0'],
        ];
    }
}
