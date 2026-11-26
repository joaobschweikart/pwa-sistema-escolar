<?php

use App\Http\Controllers\Api\V1\AlocacaoController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\AulaController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvaliacaoController;
use App\Http\Controllers\Api\V1\ConflitoController;
use App\Http\Controllers\Api\V1\DisciplinaController;
use App\Http\Controllers\Api\V1\EscolaController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProfessorController;
use App\Http\Controllers\Api\V1\SincronizacaoController;
use App\Http\Controllers\Api\V1\TurmaController;
use Illuminate\Support\Facades\Route;

// Healthcheck público (Docker, deploy com rollback automático e monitoramento)
Route::get('health', HealthController::class)->name('api.health');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/token', [AuthController::class, 'emitir'])->middleware('throttle:login')->name('auth.token');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::delete('auth/token', [AuthController::class, 'revogar'])->name('auth.revogar');
        Route::get('eu', [AuthController::class, 'eu'])->name('eu');

        Route::apiResource('escolas', EscolaController::class);
        Route::get('disciplinas', [DisciplinaController::class, 'index'])->name('disciplinas.index');
        Route::get('turmas', [TurmaController::class, 'index'])->name('turmas.index');
        Route::get('turmas/{turma}', [TurmaController::class, 'show'])->name('turmas.show');
        Route::get('turmas/{turma}/alunos', [TurmaController::class, 'alunos'])->name('turmas.alunos');

        Route::get('alocacoes', [AlocacaoController::class, 'index'])->name('alocacoes.index');
        Route::get('alocacoes/{alocacao}', [AlocacaoController::class, 'show'])->name('alocacoes.show');
        Route::get('alocacoes/{alocacao}/boletim', [AlocacaoController::class, 'boletim'])->name('alocacoes.boletim');
        Route::get('alocacoes/{alocacao}/aulas', [AulaController::class, 'index'])->name('aulas.index');
        Route::post('alocacoes/{alocacao}/aulas', [AulaController::class, 'store'])->name('aulas.store');
        Route::get('alocacoes/{alocacao}/avaliacoes', [AvaliacaoController::class, 'index'])->name('avaliacoes.index');
        Route::post('alocacoes/{alocacao}/avaliacoes', [AvaliacaoController::class, 'store'])->name('avaliacoes.store');

        Route::get('aulas/{aula}', [AulaController::class, 'show'])->name('aulas.show');
        Route::put('aulas/{aula}', [AulaController::class, 'update'])->name('aulas.update');
        Route::get('aulas/{aula}/frequencia', [AulaController::class, 'frequencia'])->name('aulas.frequencia');
        Route::put('aulas/{aula}/frequencia', [AulaController::class, 'registrarFrequencia'])->name('aulas.frequencia.registrar');

        Route::get('avaliacoes/{avaliacao}/notas', [AvaliacaoController::class, 'notas'])->name('avaliacoes.notas');
        Route::put('avaliacoes/{avaliacao}/notas', [AvaliacaoController::class, 'lancarNotas'])->name('avaliacoes.notas.lancar');

        Route::get('professor/dados', [ProfessorController::class, 'dados'])->name('professor.dados');
        Route::post('sync', SincronizacaoController::class)->middleware('throttle:sync')->name('sync');

        Route::get('conflitos', [ConflitoController::class, 'index'])->name('conflitos.index');
        Route::post('conflitos/{conflito}/resolver', [ConflitoController::class, 'resolver'])->name('conflitos.resolver');
        Route::get('auditoria/notas', [AuditoriaController::class, 'notas'])->name('auditoria.notas');
    });
});
