<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Diario\RegistrarAula;
use App\Application\Diario\RegistrarAulaComando;
use App\Application\Diario\RegistrarFrequencia;
use App\Application\Diario\RegistrarFrequenciaComando;
use App\Application\Shared\ContextoUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AulaRequest;
use App\Http\Requests\Api\FrequenciaRequest;
use App\Http\Resources\AulaResource;
use App\Http\Support\Contexto;
use App\Infrastructure\Persistence\Models\Alocacao;
use App\Infrastructure\Persistence\Models\Aula;
use App\Infrastructure\Persistence\Models\User;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

/**
 * @tags Diário
 */
class AulaController extends Controller
{
    /** Listar aulas da turma/disciplina */
    public function index(Alocacao $alocacao): AnonymousResourceCollection
    {
        $this->authorize('view', $alocacao);

        return AulaResource::collection($alocacao->aulas()->with('frequencias')->orderByDesc('data')->paginate(50));
    }

    /**
     * Registrar aula
     *
     * O `id` (UUID) pode ser gerado pelo cliente, como na PWA offline.
     */
    public function store(AulaRequest $request, Alocacao $alocacao, RegistrarAula $registrar): JsonResponse
    {
        $id = $request->string('id')->toString() ?: (string) Str::uuid();
        $resultado = $registrar->executar($this->contexto($request), new RegistrarAulaComando(
            aulaId: $id,
            alocacaoId: $alocacao->id,
            data: new DateTimeImmutable($request->string('data')->toString()),
            conteudo: $request->string('conteudo')->trim()->toString(),
            observacoes: $request->input('observacoes'),
            versaoBase: $request->integer('versao'),
            ocorridoEm: now()->toDateTimeImmutable(),
        ));

        return (new AulaResource(Aula::query()->findOrFail($id)))
            ->additional(['resultado' => $resultado->paraArray()])
            ->response()
            ->setStatusCode(201);
    }

    /** Detalhar aula com a frequência */
    public function show(Aula $aula): AulaResource
    {
        $this->authorize('view', $aula->alocacao);

        return new AulaResource($aula->load('frequencias'));
    }

    /**
     * Alterar aula
     *
     * Informe a `versao` lida. Alterações concorrentes são resolvidas por last-write-wins.
     */
    public function update(AulaRequest $request, Aula $aula, RegistrarAula $registrar): AulaResource
    {
        $resultado = $registrar->executar($this->contexto($request), new RegistrarAulaComando(
            aulaId: $aula->id,
            alocacaoId: $aula->alocacao_id,
            data: new DateTimeImmutable($request->string('data')->toString()),
            conteudo: $request->string('conteudo')->trim()->toString(),
            observacoes: $request->input('observacoes'),
            versaoBase: $request->integer('versao'),
            ocorridoEm: now()->toDateTimeImmutable(),
        ));

        return (new AulaResource($aula->refresh()))->additional(['resultado' => $resultado->paraArray()]);
    }

    /** Frequência da aula */
    public function frequencia(Aula $aula): AulaResource
    {
        $this->authorize('view', $aula->alocacao);

        return new AulaResource($aula->load('frequencias'));
    }

    /**
     * Registrar frequência
     *
     * Lança presenças e faltas da aula (last-write-wins por aluno).
     */
    public function registrarFrequencia(FrequenciaRequest $request, Aula $aula, RegistrarFrequencia $registrar): AulaResource
    {
        /** @var list<array{matricula_id: int, presente: bool, versao?: int|null}> $registros */
        $registros = $request->validated('registros');

        $resultado = $registrar->executar($this->contexto($request), new RegistrarFrequenciaComando(
            aulaId: $aula->id,
            registros: array_map(fn (array $r): array => [
                'matricula_id' => (int) $r['matricula_id'],
                'presente' => (bool) $r['presente'],
                'versao_base' => (int) ($r['versao'] ?? 0),
            ], $registros),
            ocorridoEm: now()->toDateTimeImmutable(),
        ));

        return (new AulaResource($aula->refresh()->load('frequencias')))->additional(['resultado' => $resultado->paraArray()]);
    }

    private function contexto(Request $request): ContextoUsuario
    {
        /** @var User $usuario */
        $usuario = $request->user();

        return Contexto::de($usuario);
    }
}
