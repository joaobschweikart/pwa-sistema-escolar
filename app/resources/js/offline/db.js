/**
 * pwa.offline — armazenamento local no IndexedDB.
 *  - meta:      token da API, usuário, datas de sincronização
 *  - turmas:    snapshot das turmas/disciplinas do professor (somente as dele)
 *  - fila:      lançamentos feitos offline aguardando sincronização
 */
import { openDB, deleteDB } from 'idb';

export const NOME_BANCO = 'dem-offline';
const VERSAO = 1;

let conexao;

export function abrirBanco() {
    conexao ??= openDB(NOME_BANCO, VERSAO, {
        upgrade(db) {
            db.createObjectStore('meta');
            db.createObjectStore('turmas', { keyPath: 'id' });
            const fila = db.createObjectStore('fila', { keyPath: 'uuid' });
            fila.createIndex('criadoEm', 'criadoEm');
            fila.createIndex('chave', 'chave');
        },
        blocking() {
            conexao?.then((db) => db.close());
            conexao = undefined;
        },
    });
    return conexao;
}

export async function lerMeta(chave) {
    return (await abrirBanco()).get('meta', chave);
}

export async function gravarMeta(chave, valor) {
    return (await abrirBanco()).put('meta', valor, chave);
}

export async function lerTurmas() {
    return (await abrirBanco()).getAll('turmas');
}

export async function substituirTurmas(alocacoes) {
    const db = await abrirBanco();
    const tx = db.transaction('turmas', 'readwrite');
    await tx.store.clear();
    for (const a of alocacoes) await tx.store.put(a);
    await tx.done;
}

/** Fila em ordem de criação (a ordem importa: aula antes da frequência). */
export async function lerFila() {
    return (await abrirBanco()).getAllFromIndex('fila', 'criadoEm');
}

/**
 * Enfileira uma operação. Se já existe operação pendente para o mesmo registro
 * (mesma "chave"), ela é substituída mantendo a versão-base original, para que
 * duas edições offline seguidas não gerem conflito consigo mesmas.
 */
export async function enfileirar(operacao) {
    const db = await abrirBanco();
    const tx = db.transaction('fila', 'readwrite');
    const anteriores = await tx.store.index('chave').getAll(operacao.chave);
    for (const anterior of anteriores) {
        if (anterior.enviando) continue;
        operacao.versao_base = anterior.versao_base;
        operacao.criadoEm = anterior.criadoEm;
        await tx.store.delete(anterior.uuid);
    }
    await tx.store.put(operacao);
    await tx.done;
}

export async function removerDaFila(uuids) {
    const db = await abrirBanco();
    const tx = db.transaction('fila', 'readwrite');
    for (const uuid of uuids) await tx.store.delete(uuid);
    await tx.done;
}

export async function marcarEnviando(uuids, enviando) {
    const db = await abrirBanco();
    const tx = db.transaction('fila', 'readwrite');
    for (const uuid of uuids) {
        const op = await tx.store.get(uuid);
        if (op) await tx.store.put({ ...op, enviando });
    }
    await tx.done;
}

export async function contarPendentes() {
    return (await abrirBanco()).count('fila');
}

/** Logout: apaga o IndexedDB e todos os caches do navegador para este site. */
export async function limparTudo() {
    try {
        (await conexao)?.close();
    } catch {
        /* ignora */
    }
    conexao = undefined;
    await deleteDB(NOME_BANCO);
    if ('caches' in self) {
        const nomes = await caches.keys();
        await Promise.all(nomes.map((n) => caches.delete(n)));
    }
}
