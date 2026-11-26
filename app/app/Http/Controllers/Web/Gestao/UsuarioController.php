<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Gestao;

use App\Domain\Shared\Papel;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Cadastro de usuários. Admin: todos os papéis. Gestor: professores da escola.
 */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $usuario = $this->usuario($request);

        return view('gestao.usuarios.index', [
            'usuarios' => User::query()
                ->visiveisPara($usuario)
                ->when($usuario->ehGestor(), fn ($q) => $q->where('papel', Papel::Professor))
                ->when($request->filled('papel'), fn ($q) => $q->where('papel', $request->string('papel')->toString()))
                ->with('escola')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        return view('gestao.usuarios.form', $this->dadosFormulario($request, new User(['papel' => Papel::Professor, 'ativo' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);
        User::query()->create($this->validar($request));

        return redirect()->route('gestao.usuarios.index')->with('status', 'Usuário cadastrado.');
    }

    public function edit(Request $request, User $usuario): View
    {
        $this->authorize('update', $usuario);

        return view('gestao.usuarios.form', $this->dadosFormulario($request, $usuario));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);
        $dados = $this->validar($request, $usuario);
        if (empty($dados['password'])) {
            unset($dados['password']);
        }
        $usuario->update($dados);

        if (! $usuario->ativo) {
            $usuario->tokens()->delete();
        }

        return redirect()->route('gestao.usuarios.index')->with('status', 'Usuário atualizado.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        $this->authorize('delete', $usuario);

        if ($usuario->alocacoes()->exists()) {
            return back()->withErrors(['geral' => 'O professor possui turmas vinculadas. Desative o usuário em vez de excluí-lo.']);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return redirect()->route('gestao.usuarios.index')->with('status', 'Usuário excluído.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?User $alvo = null): array
    {
        $usuario = $this->usuario($request);
        $papeis = $usuario->ehAdmin() ? array_column(Papel::cases(), 'value') : [Papel::Professor->value];

        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($alvo?->id)],
            'papel' => ['required', Rule::in($papeis)],
            'escola_id' => ['nullable', 'required_unless:papel,admin', 'exists:escolas,id'],
            'matricula_funcional' => ['nullable', 'string', 'max:30'],
            'password' => [$alvo === null ? 'required' : 'nullable', Password::min(8)->letters()->numbers()],
            'ativo' => ['boolean'],
        ]);

        if ($usuario->ehGestor()) {
            $dados['escola_id'] = $usuario->escola_id;
        }
        if ($dados['papel'] === Papel::Admin->value) {
            $dados['escola_id'] = null;
        }
        $dados['ativo'] = $request->boolean('ativo');

        return $dados;
    }

    /** @return array<string, mixed> */
    private function dadosFormulario(Request $request, User $alvo): array
    {
        $usuario = $this->usuario($request);

        return [
            'alvo' => $alvo,
            'papeis' => $usuario->ehAdmin() ? Papel::cases() : [Papel::Professor],
            'escolas' => $usuario->ehAdmin() ? Escola::query()->orderBy('nome')->get() : Escola::query()->whereKey($usuario->escola_id)->get(),
        ];
    }

    private function usuario(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
