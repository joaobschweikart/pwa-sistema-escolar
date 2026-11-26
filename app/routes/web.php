<?php

use App\Http\Controllers\Web\AutenticacaoController;
use App\Http\Controllers\Web\Gestao\AlunoController;
use App\Http\Controllers\Web\Gestao\AuditoriaController;
use App\Http\Controllers\Web\Gestao\ConflitoController;
use App\Http\Controllers\Web\Gestao\DiarioController;
use App\Http\Controllers\Web\Gestao\DisciplinaController;
use App\Http\Controllers\Web\Gestao\EscolaController;
use App\Http\Controllers\Web\Gestao\TurmaController;
use App\Http\Controllers\Web\Gestao\UsuarioController;
use App\Http\Controllers\Web\PainelController;
use App\Http\Controllers\Web\ProfessorAppController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel');

Route::middleware('guest')->group(function () {
    Route::get('login', [AutenticacaoController::class, 'formulario'])->name('login');
    Route::post('login', [AutenticacaoController::class, 'entrar'])->middleware('throttle:login');
});

Route::post('logout', [AutenticacaoController::class, 'sair'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('painel', PainelController::class)->name('painel');

    // PWA do professor (app shell que também abre offline)
    Route::get('professor', ProfessorAppController::class)->middleware('shell.offline')->name('professor.app');
    Route::post('pwa/token', [AutenticacaoController::class, 'tokenPwa'])->name('pwa.token');

    Route::prefix('gestao')->name('gestao.')->middleware('papel:admin,gestor')->group(function () {
        Route::resource('escolas', EscolaController::class)->except('show');
        Route::resource('disciplinas', DisciplinaController::class)->except('show');
        Route::resource('usuarios', UsuarioController::class)->except('show');
        Route::resource('alunos', AlunoController::class)->except('show');
        Route::resource('turmas', TurmaController::class);
        Route::post('turmas/{turma}/matriculas', [TurmaController::class, 'matricular'])->name('turmas.matricular');
        Route::patch('matriculas/{matricula}', [TurmaController::class, 'alterarMatricula'])->name('matriculas.update');
        Route::post('turmas/{turma}/alocacoes', [TurmaController::class, 'vincular'])->name('turmas.vincular');
        Route::delete('alocacoes/{alocacao}', [TurmaController::class, 'desvincular'])->name('alocacoes.destroy');
        Route::get('diario/{alocacao}', DiarioController::class)->name('diario.show');
        Route::get('conflitos', [ConflitoController::class, 'index'])->name('conflitos.index');
        Route::post('conflitos/{conflito}/resolver', [ConflitoController::class, 'resolver'])->name('conflitos.resolver');
        Route::get('auditoria', AuditoriaController::class)->name('auditoria.index');
    });
});
