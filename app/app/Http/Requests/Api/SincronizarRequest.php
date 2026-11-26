<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Domain\Sincronizacao\TipoOperacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida a estrutura do lote. O conteúdo de cada operação ("dados") é validado
 * individualmente para que uma operação inválida não bloqueie o lote inteiro.
 */
class SincronizarRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'dispositivo' => ['nullable', 'string', 'max:100'],
            'operacoes' => ['required', 'array', 'min:1', 'max:'.config('dem.sync_max_operacoes')],
            'operacoes.*.uuid' => ['required', 'uuid', 'distinct'],
            'operacoes.*.tipo' => ['required', 'string', Rule::enum(TipoOperacao::class)],
            'operacoes.*.ocorrido_em' => ['required', 'date'],
            'operacoes.*.versao_base' => ['nullable', 'integer', 'min:0'],
            'operacoes.*.dados' => ['required', 'array'],
        ];
    }
}
