/**
 * pwa.ui — Diário do professor (app shell offline-first).
 *
 * Todos os lançamentos (aula, frequência e nota) entram primeiro na fila do
 * IndexedDB e depois são enviados em lote para POST /api/v1/sync. Assim o
 * fluxo é o mesmo com ou sem internet: a tela nunca espera pela rede.
 */
import Alpine from 'alpinejs';
import { lerMeta, gravarMeta, lerTurmas, substituirTurmas, lerFila, enfileirar, limparTudo } from '../offline/db.js';
import { sincronizar, agendarSincronizacaoEmSegundoPlano, SessaoExpiradaError } from '../offline/sync.js';
import { uuid } from '../offline/uuid.js';

const CACHE_SHELL = 'dem-shell-v1';
let sequencia = 0;
const agoraOrdenado = () => Date.now() * 1000 + (sequencia++ % 1000);
const hoje = () => new Date().toLocaleDateString('sv-SE'); // AAAA-MM-DD no fuso local
const clonar = (v) => JSON.parse(JSON.stringify(v));

function situacao(media, frequencia, criterios) {
    if (frequencia !== null && frequencia < criterios.frequencia_minima) return 'Reprovado por frequência';
    if (media === null) return 'Sem lançamentos';
    return media >= criterios.media_minima ? 'Aprovado' : 'Em recuperação';
}

document.addEventListener('alpine:init', () => {
    Alpine.data('diarioProfessor', (usuarioId, usuarioNome) => ({
        usuario: { id: Number(usuarioId), nome: usuarioNome },
        online: navigator.onLine,
        carregando: true,
        sincronizando: false,
        sessaoExpirada: false,
        fila: [],
        turmas: [],
        criterios: { media_minima: 6, frequencia_minima: 75 },
        ultimaSincronizacao: null,
        avisos: [],
        promptInstalacao: null,

        // navegação
        tela: 'turmas',
        aba: 'aulas',
        alocacaoId: null,
        formAula: null,
        avaliacaoId: null,
        notasEdicao: {},
        formAvaliacao: null,
        menuAberto: false,

        async init() {
            window.addEventListener('online', () => {
                this.online = true;
                this.sincronizarAgora();
            });
            window.addEventListener('offline', () => (this.online = false));
            window.addEventListener('hashchange', () => this.lerRota());
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.promptInstalacao = e;
            });
            navigator.serviceWorker?.addEventListener('message', (e) => this.mensagemDoServiceWorker(e.data));

            await this.prepararUsuario();
            await this.recarregarLocal();
            this.lerRota();
            this.carregando = false;

            if (this.online) {
                await this.garantirShellEmCache();
                await this.sincronizarAgora();
            }
        },

        // ---------- dados locais ----------

        async prepararUsuario() {
            const anterior = await lerMeta('usuarioId');
            if (anterior && anterior !== this.usuario.id) {
                // Outro professor usa este dispositivo: descarta o cache de turmas do anterior.
                // A fila dele é mantida (filtrada por usuário) e só é enviada com a sessão dele.
                await substituirTurmas([]);
                await gravarMeta('token', null);
            }
            await gravarMeta('usuarioId', this.usuario.id);
            await gravarMeta('usuarioNome', this.usuario.nome);
        },

        async recarregarLocal() {
            this.turmas = (await lerTurmas()).sort((a, b) => a.turma.nome.localeCompare(b.turma.nome) || a.disciplina.nome.localeCompare(b.disciplina.nome));
            this.fila = (await lerFila()).filter((op) => op.usuarioId === this.usuario.id);
            this.criterios = (await lerMeta('criterios')) ?? this.criterios;
            this.ultimaSincronizacao = await lerMeta('ultimaSincronizacao');
            this.sessaoExpirada = Boolean(await lerMeta('sessaoExpirada'));
        },

        /** Garante que a página /professor esteja no cache para abrir offline. */
        async garantirShellEmCache() {
            try {
                if (!('caches' in window)) return;
                const cache = await caches.open(CACHE_SHELL);
                if (await cache.match('/professor')) return;
                const resposta = await fetch('/professor', { credentials: 'same-origin' });
                if (resposta.ok && resposta.headers.get('X-DEM-Shell') === '1') await cache.put('/professor', resposta);
            } catch {
                /* sem impacto: o service worker também guarda a página */
            }
        },

        // ---------- autenticação da API ----------

        async garantirToken(forcar = false) {
            const atual = await lerMeta('token');
            if (atual && !forcar) return atual;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const resposta = await fetch('/pwa/token', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!resposta.ok) {
                this.sessaoExpirada = true;
                await gravarMeta('sessaoExpirada', true);
                throw new SessaoExpiradaError('Sessão expirada. Entre novamente para sincronizar.');
            }
            const { token } = await resposta.json();
            await gravarMeta('token', token);
            await gravarMeta('sessaoExpirada', false);
            this.sessaoExpirada = false;
            return token;
        },

        async api(caminho, opcoes = {}, tentarNovamente = true) {
            const token = await this.garantirToken();
            const resposta = await fetch(`/api/v1${caminho}`, {
                ...opcoes,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${token}`, ...(opcoes.headers ?? {}) },
            });
            if (resposta.status === 401 && tentarNovamente) {
                await this.garantirToken(true);
                return this.api(caminho, opcoes, false);
            }
            return resposta;
        },

        async atualizarDoServidor() {
            const resposta = await this.api('/professor/dados');
            if (!resposta.ok) throw new Error(`Não foi possível baixar as turmas (HTTP ${resposta.status}).`);
            const dados = await resposta.json();
            await substituirTurmas(dados.alocacoes);
            await gravarMeta('criterios', dados.criterios);
            await gravarMeta('dadosEm', dados.gerado_em);
            await this.recarregarLocal();
        },

        // ---------- sincronização ----------

        async sincronizarAgora() {
            if (!this.online || this.sincronizando) return;
            this.sincronizando = true;
            try {
                await this.garantirToken();
                const resultado = await sincronizar();
                if (resultado?.enviadas) this.avisarResultado(resultado);
                await this.atualizarDoServidor();
            } catch (erro) {
                if (erro instanceof SessaoExpiradaError) {
                    this.sessaoExpirada = true;
                } else {
                    this.avisar('erro', erro.message ?? 'Falha na sincronização. Tentaremos novamente.');
                }
            } finally {
                this.sincronizando = false;
                await this.recarregarLocal();
            }
        },

        mensagemDoServiceWorker(msg) {
            if (msg?.tipo === 'sincronizacao-concluida') {
                if (msg.resultado?.enviadas) this.avisarResultado(msg.resultado);
                this.online ? this.atualizarDoServidor().catch(() => this.recarregarLocal()) : this.recarregarLocal();
            }
        },

        avisarResultado({ resumo, resultados }) {
            const ok = (resumo.aplicadas ?? 0) + (resumo.descartadas ?? 0);
            if (ok) this.avisar('sucesso', `${ok} lançamento(s) sincronizado(s).`);
            if (resumo.conflitos) this.avisar('alerta', `${resumo.conflitos} nota(s) com conflito enviada(s) para decisão do gestor.`);
            resultados.filter((r) => r.status === 'rejeitada').forEach((r) => this.avisar('erro', `Lançamento rejeitado: ${r.mensagem}`));
        },

        avisar(tipo, texto) {
            const id = uuid();
            this.avisos.push({ id, tipo, texto });
            setTimeout(() => (this.avisos = this.avisos.filter((a) => a.id !== id)), tipo === 'erro' ? 9000 : 5000);
        },

        async aposEnfileirar(mensagem) {
            await this.recarregarLocal();
            this.avisar('sucesso', this.online ? mensagem : `${mensagem} Será sincronizado quando a conexão voltar.`);
            await agendarSincronizacaoEmSegundoPlano();
            if (this.online) this.sincronizarAgora();
        },

        get statusConexao() {
            if (this.sessaoExpirada) return { texto: 'Sessão expirada', classe: 'bg-red-100 text-red-800' };
            if (this.sincronizando) return { texto: 'Sincronizando…', classe: 'bg-sky-100 text-sky-800' };
            if (!this.online) return { texto: this.fila.length ? `Offline · ${this.fila.length} pendente(s)` : 'Offline', classe: 'bg-amber-100 text-amber-900' };
            if (this.fila.length) return { texto: `${this.fila.length} pendente(s)`, classe: 'bg-amber-100 text-amber-900' };
            return { texto: 'Sincronizado', classe: 'bg-emerald-100 text-emerald-800' };
        },

        get ultimaSincronizacaoTexto() {
            return this.ultimaSincronizacao ? new Date(this.ultimaSincronizacao).toLocaleString('pt-BR') : 'nunca';
        },

        // ---------- rotas (#/turma/ID/aba) ----------

        lerRota() {
            const [, tela, id, aba] = location.hash.split('/');
            if (tela === 'turma' && id) {
                this.tela = 'turma';
                this.alocacaoId = Number(id);
                this.aba = ['aulas', 'notas', 'boletim'].includes(aba) ? aba : 'aulas';
            } else if (tela === 'sincronizacao') {
                this.tela = 'sincronizacao';
            } else {
                this.tela = 'turmas';
                this.alocacaoId = null;
            }
            this.formAula = null;
            this.avaliacaoId = null;
            this.formAvaliacao = null;
            this.menuAberto = false;
        },

        irPara(hash) {
            location.hash = hash;
        },

        // ---------- visão com pendências aplicadas ----------

        get alocacao() {
            const base = this.turmas.find((t) => t.id === this.alocacaoId);
            return base ? this.comPendencias(base) : null;
        },

        comPendencias(base) {
            const a = clonar(base);
            for (const op of this.fila) {
                const d = op.dados;
                if (op.tipo === 'aula.registrar' && d.alocacao_id === a.id) {
                    const aula = a.aulas.find((x) => x.id === d.aula_id);
                    if (aula) Object.assign(aula, { data: d.data, conteudo: d.conteudo, observacoes: d.observacoes, pendente: true });
                    else a.aulas.push({ id: d.aula_id, data: d.data, conteudo: d.conteudo, observacoes: d.observacoes, versao: 0, frequencias: [], pendente: true });
                }
            }
            for (const op of this.fila) {
                const d = op.dados;
                if (op.tipo === 'frequencia.registrar') {
                    const aula = a.aulas.find((x) => x.id === d.aula_id);
                    if (aula) {
                        aula.frequencias = d.registros.map((r) => ({ matricula_id: r.matricula_id, presente: r.presente, versao: r.versao_base }));
                        aula.pendente = true;
                    }
                } else if (op.tipo === 'nota.lancar') {
                    const av = a.avaliacoes.find((x) => x.id === d.avaliacao_id);
                    if (av) {
                        const nota = av.notas.find((n) => n.matricula_id === d.matricula_id);
                        if (nota) Object.assign(nota, { valor: d.valor, pendente: true });
                        else av.notas.push({ matricula_id: d.matricula_id, valor: d.valor, versao: 0, pendente: true });
                    }
                }
            }
            a.aulas.sort((x, y) => y.data.localeCompare(x.data));
            a.pendentes = this.fila.filter((op) => op.dados.alocacao_id === a.id
                || a.aulas.some((x) => x.id === op.dados.aula_id)
                || a.avaliacoes.some((x) => x.id === op.dados.avaliacao_id)).length;
            return a;
        },

        resumoTurma(base) {
            return this.comPendencias(base);
        },

        presentesNaAula(aula) {
            return aula.frequencias.filter((f) => f.presente).length;
        },

        formatarData(iso) {
            const [a, m, d] = iso.split('-');
            return `${d}/${m}/${a}`;
        },

        // ---------- aulas e frequência ----------

        novaAula() {
            const a = this.alocacao;
            this.formAula = {
                id: uuid(),
                nova: true,
                data: hoje(),
                conteudo: '',
                observacoes: '',
                versao: 0,
                presencas: Object.fromEntries(a.alunos.map((al) => [al.matricula_id, true])),
                versoesFrequencia: {},
            };
        },

        editarAula(aula) {
            const a = this.alocacao;
            const base = this.turmas.find((t) => t.id === a.id).aulas.find((x) => x.id === aula.id);
            const freq = Object.fromEntries(aula.frequencias.map((f) => [f.matricula_id, f.presente]));
            this.formAula = {
                id: aula.id,
                nova: false,
                data: aula.data,
                conteudo: aula.conteudo,
                observacoes: aula.observacoes ?? '',
                versao: base?.versao ?? 0,
                presencas: Object.fromEntries(a.alunos.map((al) => [al.matricula_id, freq[al.matricula_id] ?? true])),
                versoesFrequencia: Object.fromEntries((base?.frequencias ?? []).map((f) => [f.matricula_id, f.versao])),
            };
        },

        marcarTodos(presente) {
            Object.keys(this.formAula.presencas).forEach((k) => (this.formAula.presencas[k] = presente));
        },

        get totalPresentesForm() {
            return this.formAula ? Object.values(this.formAula.presencas).filter(Boolean).length : 0;
        },

        async salvarAula() {
            const f = this.formAula;
            if (!f.conteudo.trim()) {
                this.avisar('erro', 'Informe o conteúdo da aula.');
                return;
            }
            const ocorridoEm = new Date().toISOString();
            await enfileirar({
                uuid: uuid(),
                usuarioId: this.usuario.id,
                tipo: 'aula.registrar',
                chave: `aula:${f.id}`,
                ocorrido_em: ocorridoEm,
                versao_base: f.versao,
                criadoEm: agoraOrdenado(),
                dados: { aula_id: f.id, alocacao_id: this.alocacaoId, data: f.data, conteudo: f.conteudo.trim(), observacoes: f.observacoes?.trim() || null },
            });
            await enfileirar({
                uuid: uuid(),
                usuarioId: this.usuario.id,
                tipo: 'frequencia.registrar',
                chave: `frequencia:${f.id}`,
                ocorrido_em: ocorridoEm,
                versao_base: 0,
                criadoEm: agoraOrdenado(),
                dados: {
                    aula_id: f.id,
                    registros: Object.entries(f.presencas).map(([matricula, presente]) => ({
                        matricula_id: Number(matricula),
                        presente,
                        versao_base: f.versoesFrequencia[matricula] ?? 0,
                    })),
                },
            });
            this.formAula = null;
            await this.aposEnfileirar(f.nova ? 'Aula e frequência registradas.' : 'Aula atualizada.');
        },

        // ---------- avaliações e notas ----------

        get avaliacao() {
            return this.alocacao?.avaliacoes.find((a) => a.id === this.avaliacaoId) ?? null;
        },

        abrirNotas(avaliacao) {
            this.avaliacaoId = avaliacao.id;
            const notas = Object.fromEntries(avaliacao.notas.map((n) => [n.matricula_id, n.valor]));
            this.notasEdicao = Object.fromEntries(this.alocacao.alunos.map((al) => [al.matricula_id, notas[al.matricula_id] ?? '']));
        },

        async salvarNotas() {
            const base = this.turmas.find((t) => t.id === this.alocacaoId).avaliacoes.find((a) => a.id === this.avaliacaoId);
            const atuais = Object.fromEntries(this.avaliacao.notas.map((n) => [n.matricula_id, n.valor]));
            const versoes = Object.fromEntries(base.notas.map((n) => [n.matricula_id, n.versao]));
            let alteradas = 0;

            for (const [matricula, bruto] of Object.entries(this.notasEdicao)) {
                if (bruto === '' || bruto === null) continue;
                const valor = Math.round(Number(String(bruto).replace(',', '.')) * 100) / 100;
                if (Number.isNaN(valor) || valor < 0 || valor > 10) {
                    this.avisar('erro', 'As notas devem estar entre 0 e 10.');
                    return;
                }
                if (atuais[matricula] !== undefined && Number(atuais[matricula]) === valor) continue;
                await enfileirar({
                    uuid: uuid(),
                    usuarioId: this.usuario.id,
                    tipo: 'nota.lancar',
                    chave: `nota:${this.avaliacaoId}:${matricula}`,
                    ocorrido_em: new Date().toISOString(),
                    versao_base: versoes[matricula] ?? 0,
                    criadoEm: agoraOrdenado(),
                    dados: { avaliacao_id: this.avaliacaoId, matricula_id: Number(matricula), valor },
                });
                alteradas++;
            }
            this.avaliacaoId = null;
            if (alteradas) await this.aposEnfileirar(`${alteradas} nota(s) lançada(s).`);
        },

        novaAvaliacao() {
            this.formAvaliacao = { titulo: '', data: hoje(), periodo: 1, peso: 1 };
        },

        async salvarAvaliacao() {
            if (!this.online) {
                this.avisar('alerta', 'Criar avaliações requer conexão. Os lançamentos de notas funcionam offline.');
                return;
            }
            const resposta = await this.api(`/alocacoes/${this.alocacaoId}/avaliacoes`, { method: 'POST', body: JSON.stringify(this.formAvaliacao) });
            if (!resposta.ok) {
                const corpo = await resposta.json().catch(() => ({}));
                this.avisar('erro', corpo.message ?? 'Não foi possível criar a avaliação.');
                return;
            }
            this.formAvaliacao = null;
            this.avisar('sucesso', 'Avaliação criada.');
            await this.atualizarDoServidor();
        },

        // ---------- boletim (mesma regra do domínio: média ponderada e frequência) ----------

        get boletim() {
            const a = this.alocacao;
            if (!a) return [];
            const totalAulas = a.aulas.length;
            return a.alunos.map((al) => {
                let soma = 0;
                let pesos = 0;
                for (const av of a.avaliacoes) {
                    const n = av.notas.find((x) => x.matricula_id === al.matricula_id);
                    if (n) {
                        soma += Number(n.valor) * Number(av.peso);
                        pesos += Number(av.peso);
                    }
                }
                const media = pesos ? Math.round((soma / pesos) * 10) / 10 : null;
                const presencas = a.aulas.filter((x) => x.frequencias.some((f) => f.matricula_id === al.matricula_id && f.presente)).length;
                const frequencia = totalAulas ? Math.round((presencas / totalAulas) * 10000) / 100 : null;
                return { ...al, media, frequencia, faltas: totalAulas - presencas, situacao: situacao(media, frequencia, this.criterios) };
            });
        },

        // ---------- instalação e saída ----------

        async instalar() {
            if (!this.promptInstalacao) return;
            this.promptInstalacao.prompt();
            await this.promptInstalacao.userChoice;
            this.promptInstalacao = null;
        },

        async sair() {
            if (this.fila.length && this.online) await this.sincronizarAgora();
            if (this.fila.length && !confirm(`Há ${this.fila.length} lançamento(s) ainda não sincronizado(s). Se sair agora eles serão perdidos. Deseja sair mesmo assim?`)) {
                return;
            }
            try {
                const token = await lerMeta('token');
                if (token && this.online) {
                    await fetch('/api/v1/auth/token', { method: 'DELETE', headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } });
                }
            } catch {
                /* o servidor também revoga o token no logout */
            }
            await limparTudo();
            navigator.serviceWorker?.controller?.postMessage({ tipo: 'limpar-cache' });
            const form = this.$refs.formSair;
            form.dataset.limpo = '1';
            form.submit();
        },
    }));
});
