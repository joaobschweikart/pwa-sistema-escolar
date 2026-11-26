@props(['nome', 'rotulo', 'tipo' => 'text', 'valor' => null, 'obrigatorio' => false, 'ajuda' => null])
<div>
    <label for="{{ $nome }}" class="campo-rotulo">{{ $rotulo }}@if ($obrigatorio)<span class="text-red-600"> *</span>@endif</label>
    @if ($tipo === 'textarea')
        <textarea id="{{ $nome }}" name="{{ $nome }}" rows="3" @required($obrigatorio) {{ $attributes->merge(['class' => 'campo']) }}>{{ old($nome, $valor) }}</textarea>
    @else
        <input id="{{ $nome }}" name="{{ $nome }}" type="{{ $tipo }}" value="{{ $tipo === 'password' ? '' : old($nome, $valor) }}" @required($obrigatorio) {{ $attributes->merge(['class' => 'campo']) }}>
    @endif
    @if ($ajuda)<p class="mt-1 text-xs text-slate-500">{{ $ajuda }}</p>@endif
    @error($nome)<p class="campo-erro">{{ $message }}</p>@enderror
</div>
