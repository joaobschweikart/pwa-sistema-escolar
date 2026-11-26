@props(['nome', 'rotulo', 'opcoes' => [], 'valor' => null, 'obrigatorio' => false, 'vazio' => null])
<div>
    <label for="{{ $nome }}" class="campo-rotulo">{{ $rotulo }}@if ($obrigatorio)<span class="text-red-600"> *</span>@endif</label>
    <select id="{{ $nome }}" name="{{ $nome }}" @required($obrigatorio) {{ $attributes->merge(['class' => 'campo']) }}>
        @if ($vazio !== null)<option value="">{{ $vazio }}</option>@endif
        @foreach ($opcoes as $chave => $texto)
            <option value="{{ $chave }}" @selected((string) old($nome, $valor) === (string) $chave)>{{ $texto }}</option>
        @endforeach
    </select>
    @error($nome)<p class="campo-erro">{{ $message }}</p>@enderror
</div>
