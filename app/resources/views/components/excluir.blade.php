@props(['acao', 'confirmacao' => 'Confirma a exclusão?'])
<form method="POST" action="{{ $acao }}" onsubmit="return confirm(@js($confirmacao))" class="inline">
    @csrf @method('DELETE')
    <button type="submit" class="btn-fantasma px-3 text-red-700" title="Excluir"><x-icone nome="lixeira" /><span class="sr-only md:not-sr-only">Excluir</span></button>
</form>
