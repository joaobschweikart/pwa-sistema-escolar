@if (session('status'))
    <div role="status" class="mb-4 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <x-icone nome="check" class="mt-0.5 size-5 shrink-0" /> <span>{{ session('status') }}</span>
    </div>
@endif
@if ($errors->has('geral'))
    <div role="alert" class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <x-icone nome="alerta" class="mt-0.5 size-5 shrink-0" /> <span>{{ $errors->first('geral') }}</span>
    </div>
@endif
