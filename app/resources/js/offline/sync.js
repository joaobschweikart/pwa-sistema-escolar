/**
 * pwa.offline — sincronização da fila de lançamentos com POST /api/v1/sync.
 * Usado pela página (evento "online", botão "Sincronizar") e pelo service
 * worker (Background Sync). Um Web Lock impede duas sincronizações simultâneas.
 */
import { lerFila, lerMeta, gravarMeta, removerDaFila, marcarEnviando } from './db.js';

const TAMANHO_LOTE = 100;

export class SessaoExpiradaError extends Error {}

async function comBloqueio(fn) {
    if (globalThis.navigator?.locks?.request) {
        return navigator.locks.request('dem-sincronizacao', { ifAvailable: true }, (lock) => (lock ? fn() : null));
    }
    return fn();
}

/**
 * Envia a fila em lotes. Operações com resposta definitiva (aplicada,
 * descartada, conflito, rejeitada, duplicada) saem da fila; em erro de rede
 * ou do servidor elas permanecem para a próxima tentativa.
 *
 * @returns {Promise<null|{enviadas:number, resultados:Array, resumo:object}>}
 */
export function sincronizar() {
    return comBloqueio(async () => {
        const token = await lerMeta('token');
        const usuarioId = await lerMeta('usuarioId');
        if (!token || !usuarioId) return { enviadas: 0, resultados: [], resumo: {} };

        const fila = (await lerFila()).filter((op) => op.usuarioId === usuarioId);
        const resultados = [];
        const resumo = { aplicadas: 0, descartadas: 0, conflitos: 0, rejeitadas: 0, duplicadas: 0 };

        for (let i = 0; i < fila.length; i += TAMANHO_LOTE) {
            const lote = fila.slice(i, i + TAMANHO_LOTE);
            const uuids = lote.map((op) => op.uuid);
            await marcarEnviando(uuids, true);

            let resposta;
            try {
                resposta = await fetch('/api/v1/sync', {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
                    body: JSON.stringify({
                        dispositivo: (globalThis.navigator?.userAgent ?? '').slice(0, 100),
                        operacoes: lote.map(({ uuid, tipo, ocorrido_em, versao_base, dados }) => ({ uuid, tipo, ocorrido_em, versao_base, dados })),
                    }),
                });
            } catch (erro) {
                await marcarEnviando(uuids, false);
                throw erro; // sem rede: o lote fica na fila
            }

            if (resposta.status === 401) {
                await marcarEnviando(uuids, false);
                await gravarMeta('sessaoExpirada', true);
                throw new SessaoExpiradaError('Sessão expirada. Entre novamente para sincronizar.');
            }
            if (!resposta.ok) {
                await marcarEnviando(uuids, false);
                throw new Error(`Falha na sincronização (HTTP ${resposta.status}).`);
            }

            const corpo = await resposta.json();
            await removerDaFila(corpo.resultados.map((r) => r.uuid));
            resultados.push(...corpo.resultados);
            for (const chave of Object.keys(resumo)) resumo[chave] += corpo.resumo?.[chave] ?? 0;
        }

        const agora = new Date().toISOString();
        await gravarMeta('ultimaSincronizacao', agora);
        if (resultados.length) await gravarMeta('ultimosResultados', { em: agora, resultados, resumo });
        return { enviadas: resultados.length, resultados, resumo };
    });
}

/** Pede ao navegador uma sincronização em segundo plano (quando suportado). */
export async function agendarSincronizacaoEmSegundoPlano() {
    try {
        const registro = await navigator.serviceWorker?.ready;
        await registro?.sync?.register('dem-sincronizar');
        return Boolean(registro?.sync);
    } catch {
        return false;
    }
}
