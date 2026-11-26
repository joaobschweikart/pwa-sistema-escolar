<?php

use App\Infrastructure\Persistence\Models\OperacaoSincronizacao;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('dem:usuarios-count', function () {
    $this->line((string) User::query()->count());
})->purpose('Quantidade de usuários (usado pelo entrypoint de desenvolvimento)');

Artisan::command('dem:health', function () {
    DB::select('select 1');
    $this->info('ok');
})->purpose('Verifica a conexão com o banco (healthcheck)');

Artisan::command('dem:limpar-sync', function () {
    $dias = (int) config('dem.sync_retencao_dias');
    $removidas = OperacaoSincronizacao::query()->where('created_at', '<', now()->subDays($dias))->delete();
    $this->info("{$removidas} operação(ões) de sincronização removida(s).");
})->purpose('Remove registros antigos de operações de sincronização');

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('dem:limpar-sync')->weekly();
