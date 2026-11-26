<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Infrastructure\Persistence\Models\Escola;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscolaRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $escola = $this->route('escola');

        return [
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'nome' => ['required', 'string', 'max:255'],
            'codigo_inep' => ['nullable', 'digits:8', Rule::unique('escolas', 'codigo_inep')->ignore($escola instanceof Escola ? $escola->id : null)],
            'endereco' => ['nullable', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
