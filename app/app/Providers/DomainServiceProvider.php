<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Acesso\Contracts\VinculoRepository;
use App\Domain\Avaliacao\Contracts\AuditoriaNotaRepository;
use App\Domain\Avaliacao\Contracts\DadosBoletimRepository;
use App\Domain\Avaliacao\Contracts\NotaRepository;
use App\Domain\Avaliacao\CriteriosAprovacao;
use App\Domain\Diario\Contracts\AulaRepository;
use App\Domain\Diario\Contracts\FrequenciaRepository;
use App\Domain\Shared\Contracts\GerenciadorTransacao;
use App\Domain\Shared\Contracts\Relogio;
use App\Domain\Sincronizacao\Contracts\ConflitoNotaRepository;
use App\Domain\Sincronizacao\Contracts\OperacaoSincronizacaoRepository;
use App\Infrastructure\Persistence\Repositories\EloquentAuditoriaNotaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentAulaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentConflitoNotaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentDadosBoletimRepository;
use App\Infrastructure\Persistence\Repositories\EloquentFrequenciaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentNotaRepository;
use App\Infrastructure\Persistence\Repositories\EloquentOperacaoSincronizacaoRepository;
use App\Infrastructure\Persistence\Repositories\EloquentVinculoRepository;
use App\Infrastructure\Support\RelogioSistema;
use App\Infrastructure\Support\TransacaoLaravel;
use Illuminate\Support\ServiceProvider;

/**
 * Inversão de dependência: liga os contratos declarados em app.domain às
 * implementações de app.infrastructure (repositórios Eloquent etc.).
 */
class DomainServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        AulaRepository::class => EloquentAulaRepository::class,
        FrequenciaRepository::class => EloquentFrequenciaRepository::class,
        NotaRepository::class => EloquentNotaRepository::class,
        AuditoriaNotaRepository::class => EloquentAuditoriaNotaRepository::class,
        DadosBoletimRepository::class => EloquentDadosBoletimRepository::class,
        VinculoRepository::class => EloquentVinculoRepository::class,
        OperacaoSincronizacaoRepository::class => EloquentOperacaoSincronizacaoRepository::class,
        ConflitoNotaRepository::class => EloquentConflitoNotaRepository::class,
        GerenciadorTransacao::class => TransacaoLaravel::class,
        Relogio::class => RelogioSistema::class,
    ];

    public function register(): void
    {
        $this->app->singleton(CriteriosAprovacao::class, fn () => new CriteriosAprovacao(
            mediaMinima: (float) config('dem.media_minima', 6.0),
            frequenciaMinima: (float) config('dem.frequencia_minima', 75.0),
        ));
    }
}
