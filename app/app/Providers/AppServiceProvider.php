<?php

namespace App\Providers;

use App\Http\Policies\AlocacaoPolicy;
use App\Http\Policies\AlunoPolicy;
use App\Http\Policies\ConflitoNotaPolicy;
use App\Http\Policies\DisciplinaPolicy;
use App\Http\Policies\EscolaPolicy;
use App\Http\Policies\TurmaPolicy;
use App\Http\Policies\UserPolicy;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aluno;
use App\Infrastructure\Persistence\Models\ConflitoNota;
use App\Infrastructure\Persistence\Models\Disciplina;
use App\Infrastructure\Persistence\Models\Escola;
use App\Infrastructure\Persistence\Models\Turma;
use App\Infrastructure\Persistence\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('paginacao');
        Paginator::defaultSimpleView('paginacao');

        $this->registrarPolicies();
        $this->configurarRateLimits();
        $this->configurarDocumentacaoApi();
    }

    private function registrarPolicies(): void
    {
        Gate::policy(Escola::class, EscolaPolicy::class);
        Gate::policy(Disciplina::class, DisciplinaPolicy::class);
        Gate::policy(Turma::class, TurmaPolicy::class);
        Gate::policy(Alocacao::class, AlocacaoPolicy::class);
        Gate::policy(Aluno::class, AlunoPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ConflitoNota::class, ConflitoNotaPolicy::class);

        // Documentação da API (OpenAPI) pública: descreve os endpoints, não expõe dados
        Gate::define('viewApiDocs', fn (?User $user) => true);
    }

    private function configurarRateLimits(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('sync', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));
    }

    private function configurarDocumentacaoApi(): void
    {
        Scramble::configure()
            ->routes(fn ($route) => str_starts_with($route->uri, 'api/'))
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(SecurityScheme::http('bearer'));
            });
    }
}
