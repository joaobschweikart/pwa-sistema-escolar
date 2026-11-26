<x-layouts.base titulo="Diário do professor" :vite="['resources/css/app.css', 'resources/js/app.js', 'resources/js/professor/app.js']">
<div x-data="diarioProfessor(@js($usuario->id), @js($usuario->name))" class="min-h-full" data-testid="app-professor">

    {{-- Cabeçalho com status de conexão/sincronização --}}
    <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-3xl items-center gap-2 px-4">
            <a href="#/" class="flex min-h-11 items-center gap-2 font-bold text-marca-800">
                <img src="/icons/icon.svg" alt="" class="size-8"><span class="hidden sm:inline">Diário DEM</span>
            </a>
            <span class="selo ml-1" :class="statusConexao.classe" x-text="statusConexao.texto" data-testid="status-sync" role="status" aria-live="polite"></span>
            <div class="ml-auto flex items-center gap-1">
                <button type="button" x-show="promptInstalacao" x-cloak @click="instalar()" class="btn-fantasma px-3" title="Instalar aplicativo">
                    <x-icone nome="baixar" /><span class="hidden sm:inline">Instalar</span>
                </button>
                <button type="button" @click="sincronizarAgora()" :disabled="!online || sincronizando" class="btn-fantasma px-3" title="Sincronizar agora" data-testid="botao-sincronizar">
                    <x-icone nome="sincronizar" x-bind:class="sincronizando && 'animate-spin'" /><span class="sr-only">Sincronizar agora</span>
                </button>
                <button type="button" @click="sair()" class="btn-fantasma hidden px-3 md:inline-flex"><x-icone nome="sair" /> Sair</button>
            </div>
        </div>
    </header>

    <div x-show="sessaoExpirada" x-cloak class="bg-red-50 px-4 py-3 text-sm text-red-900">
        <div class="mx-auto flex max-w-3xl flex-wrap items-center gap-2">
            Sua sessão expirou. Os lançamentos continuam guardados neste aparelho.
            <a href="{{ route('login') }}" class="font-semibold underline">Entrar novamente para sincronizar</a>
        </div>
    </div>
    <div x-show="!online" x-cloak class="bg-amber-50 px-4 py-2 text-sm text-amber-900" data-testid="aviso-offline">
        <div class="mx-auto flex max-w-3xl items-center gap-2">
            <x-icone nome="nuvem-off" class="size-5 shrink-0" />
            Sem conexão. Continue registrando normalmente: os lançamentos serão enviados quando a internet voltar.
        </div>
    </div>

    <main class="com-nav-inferior mx-auto max-w-3xl px-4 py-5">
        <div x-show="carregando" class="space-y-3" aria-busy="true">
            <div class="h-20 animate-pulse rounded-xl bg-slate-200"></div>
            <div class="h-20 animate-pulse rounded-xl bg-slate-200"></div>
        </div>

        {{-- ===================== Minhas turmas ===================== --}}
        <section x-show="!carregando && tela === 'turmas'" x-cloak>
            <h1 class="text-xl font-bold">Minhas turmas</h1>
            <p class="mb-4 text-sm text-slate-600">Olá, <span x-text="usuario.nome.split(' ')[0]"></span>. Escolha a turma para registrar aula, frequência e notas.</p>
            <template x-if="turmas.length === 0">
                <div class="cartao p-6 text-center text-sm text-slate-600">
                    <p x-show="online">Nenhuma turma vinculada a você. Procure o gestor da escola.</p>
                    <p x-show="!online">Suas turmas ainda não foram baixadas neste aparelho. Conecte-se à internet uma vez para usar o modo offline.</p>
                </div>
            </template>
            <ul class="grid gap-3 sm:grid-cols-2">
                <template x-for="t in turmas" :key="t.id">
                    <li>
                        <a :href="`#/turma/${t.id}/aulas`" class="cartao block p-4 hover:border-marca-600" data-testid="cartao-turma">
                            <p class="font-semibold" x-text="`${t.turma.nome} · ${t.disciplina.nome}`"></p>
                            <p class="text-sm text-slate-600" x-text="t.turma.escola"></p>
                            <p class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                <span x-text="`${t.alunos.length} alunos`"></span>·
                                <span x-text="`${t.aulas.length} aulas`"></span>
                                <span x-show="resumoTurma(t).pendentes" class="selo bg-amber-100 text-amber-900" x-text="`${resumoTurma(t).pendentes} pendente(s)`"></span>
                            </p>
                        </a>
                    </li>
                </template>
            </ul>
        </section>

        {{-- ===================== Turma ===================== --}}
        <section x-show="!carregando && tela === 'turma' && alocacao" x-cloak>
            <template x-if="alocacao">
                <div>
                    <a href="#/" class="mb-2 inline-flex min-h-11 items-center gap-1 text-sm font-medium text-marca-700"><x-icone nome="voltar" class="size-4" /> Minhas turmas</a>
                    <h1 class="text-xl font-bold" x-text="`${alocacao.turma.nome} · ${alocacao.disciplina.nome}`" data-testid="titulo-turma"></h1>
                    <p class="text-sm text-slate-600" x-text="alocacao.turma.escola"></p>

                    <nav class="mt-4 grid grid-cols-3 gap-1 rounded-xl bg-slate-200/70 p-1" aria-label="Seções da turma">
                        <template x-for="[id, rotulo] in [['aulas', 'Aulas'], ['notas', 'Notas'], ['boletim', 'Boletim']]" :key="id">
                            <a :href="`#/turma/${alocacao.id}/${id}`" class="flex min-h-11 items-center justify-center rounded-lg text-sm font-semibold"
                               :class="aba === id ? 'bg-white text-marca-800 shadow-sm' : 'text-slate-600'" x-text="rotulo" :data-testid="`aba-${id}`"></a>
                        </template>
                    </nav>

                    {{-- -------- Aulas e frequência -------- --}}
                    <div x-show="aba === 'aulas'" class="mt-4">
                        <template x-if="!formAula">
                            <div>
                                <button type="button" @click="novaAula()" class="btn-primario w-full sm:w-auto" data-testid="botao-nova-aula"><x-icone nome="mais" /> Registrar aula</button>
                                <ul class="mt-4 space-y-2">
                                    <template x-for="aula in alocacao.aulas" :key="aula.id">
                                        <li>
                                            <button type="button" @click="editarAula(aula)" class="cartao block w-full p-4 text-left hover:border-marca-600" data-testid="item-aula">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p class="font-medium" x-text="aula.conteudo"></p>
                                                    <span x-show="aula.pendente" class="selo shrink-0 bg-amber-100 text-amber-900">pendente</span>
                                                </div>
                                                <p class="mt-1 text-sm text-slate-600">
                                                    <span x-text="formatarData(aula.data)"></span> ·
                                                    <span x-text="aula.frequencias.length ? `${presentesNaAula(aula)}/${aula.frequencias.length} presentes` : 'sem chamada'"></span>
                                                </p>
                                            </button>
                                        </li>
                                    </template>
                                    <li x-show="alocacao.aulas.length === 0" class="cartao p-6 text-center text-sm text-slate-500">Nenhuma aula registrada.</li>
                                </ul>
                            </div>
                        </template>

                        <template x-if="formAula">
                            <form @submit.prevent="salvarAula()" class="space-y-4" data-testid="form-aula">
                                <h2 class="text-lg font-semibold" x-text="formAula.nova ? 'Nova aula' : 'Editar aula'"></h2>
                                <div class="cartao space-y-4 p-4">
                                    <div>
                                        <label class="campo-rotulo" for="aula-data">Data</label>
                                        <input id="aula-data" type="date" x-model="formAula.data" class="campo" required>
                                    </div>
                                    <div>
                                        <label class="campo-rotulo" for="aula-conteudo">Conteúdo ministrado</label>
                                        <textarea id="aula-conteudo" x-model="formAula.conteudo" rows="3" class="campo" required placeholder="Ex.: Frações equivalentes — exercícios das páginas 40 a 42" data-testid="campo-conteudo"></textarea>
                                    </div>
                                    <div>
                                        <label class="campo-rotulo" for="aula-obs">Observações (opcional)</label>
                                        <input id="aula-obs" type="text" x-model="formAula.observacoes" class="campo">
                                    </div>
                                </div>

                                <div class="cartao p-4">
                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="font-semibold">Chamada <span class="text-sm font-normal text-slate-600" x-text="`(${totalPresentesForm}/${alocacao.alunos.length} presentes)`"></span></h3>
                                        <div class="flex gap-2">
                                            <button type="button" class="btn-secundario px-3" @click="marcarTodos(true)">Todos presentes</button>
                                        </div>
                                    </div>
                                    <ul class="divide-y divide-slate-100">
                                        <template x-for="aluno in alocacao.alunos" :key="aluno.matricula_id">
                                            <li class="flex items-center justify-between gap-3 py-1.5">
                                                <span class="min-w-0 truncate text-sm"><span class="mr-2 text-slate-400" x-text="String(aluno.numero_chamada ?? '').padStart(2, '0')"></span><span x-text="aluno.nome"></span></span>
                                                <button type="button" @click="formAula.presencas[aluno.matricula_id] = !formAula.presencas[aluno.matricula_id]"
                                                        class="btn min-w-24 shrink-0 px-3"
                                                        :class="formAula.presencas[aluno.matricula_id] ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                                        :aria-pressed="formAula.presencas[aluno.matricula_id].toString()"
                                                        :aria-label="`${aluno.nome}: ${formAula.presencas[aluno.matricula_id] ? 'presente' : 'falta'}`"
                                                        data-testid="botao-presenca"
                                                        x-text="formAula.presencas[aluno.matricula_id] ? 'Presente' : 'Falta'"></button>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <div class="sticky bottom-20 flex gap-2 md:bottom-4">
                                    <button type="button" @click="formAula = null" class="btn-secundario flex-1">Cancelar</button>
                                    <button type="submit" class="btn-primario flex-1" data-testid="botao-salvar-aula"><x-icone nome="check" /> Salvar</button>
                                </div>
                            </form>
                        </template>
                    </div>

                    {{-- -------- Avaliações e notas -------- --}}
                    <div x-show="aba === 'notas'" class="mt-4">
                        <template x-if="!avaliacaoId && !formAvaliacao">
                            <div>
                                <button type="button" @click="novaAvaliacao()" :disabled="!online" class="btn-secundario w-full sm:w-auto">
                                    <x-icone nome="mais" /> Nova avaliação
                                </button>
                                <p x-show="!online" class="mt-1 text-xs text-slate-500">Criar avaliações requer conexão. Lançar notas funciona offline.</p>
                                <ul class="mt-4 space-y-2">
                                    <template x-for="av in alocacao.avaliacoes" :key="av.id">
                                        <li>
                                            <button type="button" @click="abrirNotas(av)" class="cartao block w-full p-4 text-left hover:border-marca-600" data-testid="item-avaliacao">
                                                <p class="font-medium" x-text="av.titulo"></p>
                                                <p class="mt-1 text-sm text-slate-600">
                                                    <span x-text="formatarData(av.data)"></span> · <span x-text="`${av.periodo}º bim.`"></span> · <span x-text="`peso ${av.peso}`"></span> ·
                                                    <span x-text="`${av.notas.length}/${alocacao.alunos.length} notas`"></span>
                                                    <span x-show="av.notas.some(n => n.pendente)" class="selo bg-amber-100 text-amber-900">pendente</span>
                                                </p>
                                            </button>
                                        </li>
                                    </template>
                                    <li x-show="alocacao.avaliacoes.length === 0" class="cartao p-6 text-center text-sm text-slate-500">Nenhuma avaliação cadastrada.</li>
                                </ul>
                            </div>
                        </template>

                        <template x-if="formAvaliacao">
                            <form @submit.prevent="salvarAvaliacao()" class="cartao space-y-4 p-4">
                                <h2 class="text-lg font-semibold">Nova avaliação</h2>
                                <div><label class="campo-rotulo" for="av-titulo">Título</label><input id="av-titulo" x-model="formAvaliacao.titulo" class="campo" required maxlength="120"></div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-3 sm:col-span-1"><label class="campo-rotulo" for="av-data">Data</label><input id="av-data" type="date" x-model="formAvaliacao.data" class="campo" required></div>
                                    <div><label class="campo-rotulo" for="av-periodo">Bimestre</label>
                                        <select id="av-periodo" x-model.number="formAvaliacao.periodo" class="campo"><option value="1">1º</option><option value="2">2º</option><option value="3">3º</option><option value="4">4º</option></select></div>
                                    <div><label class="campo-rotulo" for="av-peso">Peso</label><input id="av-peso" type="number" step="0.5" min="0.5" max="10" inputmode="decimal" x-model.number="formAvaliacao.peso" class="campo" required></div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" @click="formAvaliacao = null" class="btn-secundario flex-1">Cancelar</button>
                                    <button type="submit" class="btn-primario flex-1">Criar</button>
                                </div>
                            </form>
                        </template>

                        <template x-if="avaliacaoId && avaliacao">
                            <form @submit.prevent="salvarNotas()" class="space-y-4" data-testid="form-notas">
                                <div>
                                    <h2 class="text-lg font-semibold" x-text="avaliacao.titulo"></h2>
                                    <p class="text-sm text-slate-600" x-text="`Peso ${avaliacao.peso} · notas de 0 a 10`"></p>
                                </div>
                                <ul class="cartao divide-y divide-slate-100">
                                    <template x-for="aluno in alocacao.alunos" :key="aluno.matricula_id">
                                        <li class="flex items-center justify-between gap-3 px-4 py-2">
                                            <label :for="`nota-${aluno.matricula_id}`" class="min-w-0 truncate text-sm">
                                                <span class="mr-2 text-slate-400" x-text="String(aluno.numero_chamada ?? '').padStart(2, '0')"></span><span x-text="aluno.nome"></span>
                                            </label>
                                            <input :id="`nota-${aluno.matricula_id}`" type="number" inputmode="decimal" step="0.1" min="0" max="10"
                                                   x-model="notasEdicao[aluno.matricula_id]" class="campo w-24 shrink-0 text-right" data-testid="campo-nota">
                                        </li>
                                    </template>
                                </ul>
                                <div class="sticky bottom-20 flex gap-2 md:bottom-4">
                                    <button type="button" @click="avaliacaoId = null" class="btn-secundario flex-1">Cancelar</button>
                                    <button type="submit" class="btn-primario flex-1" data-testid="botao-salvar-notas"><x-icone nome="check" /> Salvar notas</button>
                                </div>
                            </form>
                        </template>
                    </div>

                    {{-- -------- Boletim -------- --}}
                    <div x-show="aba === 'boletim'" class="mt-4">
                        <p class="mb-3 text-xs text-slate-500" x-text="`Média ponderada pelos pesos · aprovação com média ≥ ${criterios.media_minima} e frequência ≥ ${criterios.frequencia_minima}%`"></p>
                        <ul class="space-y-2">
                            <template x-for="b in boletim" :key="b.matricula_id">
                                <li class="cartao flex items-center justify-between gap-3 p-3" data-testid="linha-boletim">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium" x-text="b.nome"></p>
                                        <p class="text-xs text-slate-500" x-text="`Frequência ${b.frequencia !== null ? b.frequencia.toLocaleString('pt-BR') + '%' : '—'} · ${b.faltas} falta(s)`"></p>
                                        <span class="selo mt-1" :class="{
                                            'bg-emerald-100 text-emerald-800': b.situacao === 'Aprovado',
                                            'bg-amber-100 text-amber-900': b.situacao === 'Em recuperação',
                                            'bg-red-100 text-red-800': b.situacao === 'Reprovado por frequência',
                                            'bg-slate-100 text-slate-700': b.situacao === 'Sem lançamentos' }" x-text="b.situacao"></span>
                                    </div>
                                    <p class="text-2xl font-bold" x-text="b.media !== null ? b.media.toLocaleString('pt-BR', { minimumFractionDigits: 1 }) : '—'"></p>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>
        </section>

        {{-- ===================== Sincronização ===================== --}}
        <section x-show="!carregando && tela === 'sincronizacao'" x-cloak>
            <h1 class="text-xl font-bold">Sincronização</h1>
            <div class="cartao mt-4 space-y-2 p-4 text-sm">
                <p>Situação: <span class="selo" :class="statusConexao.classe" x-text="statusConexao.texto"></span></p>
                <p>Última sincronização: <strong x-text="ultimaSincronizacaoTexto"></strong></p>
                <p class="text-slate-600">Os dados ficam somente neste aparelho até serem enviados. Ao sair do sistema, o cache e os dados locais são apagados.</p>
                <button type="button" @click="sincronizarAgora()" :disabled="!online || sincronizando" class="btn-primario mt-2 w-full sm:w-auto">
                    <x-icone nome="sincronizar" /> Sincronizar agora
                </button>
            </div>
            <h2 class="mt-6 mb-2 font-semibold" x-text="`Lançamentos pendentes (${fila.length})`"></h2>
            <ul class="cartao divide-y divide-slate-100 text-sm" data-testid="lista-pendentes">
                <template x-for="op in fila" :key="op.uuid">
                    <li class="px-4 py-3">
                        <p class="font-medium" x-text="{ 'aula.registrar': 'Aula', 'frequencia.registrar': 'Frequência', 'nota.lancar': 'Nota' }[op.tipo]"></p>
                        <p class="text-xs text-slate-500" x-text="`${new Date(op.ocorrido_em).toLocaleString('pt-BR')} · ${op.uuid.slice(0, 8)}`"></p>
                    </li>
                </template>
                <li x-show="fila.length === 0" class="px-4 py-6 text-center text-slate-500">Nada pendente. Tudo foi enviado ao servidor.</li>
            </ul>
        </section>
    </main>

    {{-- Navegação inferior (celular) --}}
    <nav class="nav-inferior fixed inset-x-0 bottom-0 z-30 grid grid-cols-3 border-t border-slate-200 bg-white md:hidden" aria-label="Navegação inferior">
        <a href="#/" class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] font-medium" :class="tela !== 'sincronizacao' ? 'text-marca-700' : 'text-slate-600'">
            <x-icone nome="turmas" class="size-6" /> Turmas
        </a>
        <a href="#/sincronizacao" class="relative flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] font-medium" :class="tela === 'sincronizacao' ? 'text-marca-700' : 'text-slate-600'" data-testid="nav-sincronizacao">
            <x-icone nome="sincronizar" class="size-6" /> Sincronização
            <span x-show="fila.length" x-cloak class="absolute top-1.5 right-1/2 translate-x-6 rounded-full bg-amber-500 px-1.5 text-[10px] font-bold text-white" x-text="fila.length" data-testid="badge-pendentes"></span>
        </a>
        <button type="button" @click="sair()" class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-[11px] font-medium text-slate-600">
            <x-icone nome="sair" class="size-6" /> Sair
        </button>
    </nav>

    {{-- Avisos --}}
    <div class="pointer-events-none fixed inset-x-0 bottom-20 z-40 flex flex-col items-center gap-2 px-4 md:bottom-6" aria-live="polite">
        <template x-for="a in avisos" :key="a.id">
            <div class="pointer-events-auto w-full max-w-md rounded-lg px-4 py-3 text-sm shadow-lg" data-testid="aviso"
                 :class="{ 'bg-emerald-700 text-white': a.tipo === 'sucesso', 'bg-amber-500 text-slate-900': a.tipo === 'alerta', 'bg-red-700 text-white': a.tipo === 'erro' }"
                 x-text="a.texto"></div>
        </template>
    </div>

    <form x-ref="formSair" method="POST" action="{{ route('logout') }}" data-logout class="hidden">@csrf</form>
</div>
</x-layouts.base>
