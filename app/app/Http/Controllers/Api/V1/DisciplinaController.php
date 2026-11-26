<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DisciplinaResource;
use App\Infrastructure\Persistence\Models\Disciplina;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Cadastros
 */
class DisciplinaController extends Controller
{
    /** Listar disciplinas */
    public function index(): AnonymousResourceCollection
    {
        return DisciplinaResource::collection(Disciplina::query()->orderBy('nome')->get());
    }
}
