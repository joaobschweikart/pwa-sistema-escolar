<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * App shell da PWA do professor. Os dados vêm da API e ficam no IndexedDB,
 * por isso a mesma página abre com ou sem conexão.
 */
class ProfessorAppController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        /** @var User $usuario */
        $usuario = $request->user();

        if (! $usuario->ehProfessor()) {
            return redirect()->route('painel');
        }

        return view('professor.app', ['usuario' => $usuario]);
    }
}
