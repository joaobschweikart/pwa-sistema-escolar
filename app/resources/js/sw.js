/**
 * pwa.offline — service worker do DEM (Workbox, estratégia injectManifest).
 *  - precache dos assets compilados (app shell estático)
 *  - página /professor: rede primeiro, com cópia em cache para abrir offline
 *    (somente respostas marcadas com X-DEM-Shell, isto é, a PWA do professor)
 *  - demais navegações sem rede: página offline.html
 *  - Background Sync: envia a fila do IndexedDB quando a conexão volta
 */
import { precacheAndRoute, cleanupOutdatedCaches, matchPrecache } from 'workbox-precaching';
import { registerRoute, setCatchHandler } from 'workbox-routing';
import { NetworkOnly } from 'workbox-strategies';
import { clientsClaim } from 'workbox-core';
import { sincronizar } from './offline/sync.js';

const CACHE_SHELL = 'dem-shell-v1';
const URL_SHELL = '/professor';
const TEMPO_LIMITE_REDE_MS = 5000;

self.skipWaiting();
clientsClaim();
cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

function comTempoLimite(promessa, ms) {
    return Promise.race([promessa, new Promise((_, rejeitar) => setTimeout(() => rejeitar(new Error('tempo esgotado')), ms))]);
}

async function shellEmCache() {
    const cache = await caches.open(CACHE_SHELL);
    return cache.match(URL_SHELL);
}

// App shell do professor: rede primeiro (dados frescos), cache quando offline
registerRoute(
    ({ request, url }) => request.mode === 'navigate' && url.pathname === URL_SHELL,
    async ({ request }) => {
        try {
            const resposta = await comTempoLimite(fetch(request), TEMPO_LIMITE_REDE_MS);
            if (resposta.ok && resposta.headers.get('X-DEM-Shell') === '1') {
                const cache = await caches.open(CACHE_SHELL);
                await cache.put(URL_SHELL, resposta.clone());
            }
            return resposta;
        } catch {
            return (await shellEmCache()) ?? (await matchPrecache('/offline.html')) ?? Response.error();
        }
    },
);

// API sempre pela rede: os dados offline ficam no IndexedDB, não no Cache Storage
registerRoute(({ url }) => url.pathname.startsWith('/api/'), new NetworkOnly());

// Outras páginas sem conexão: página offline
setCatchHandler(async ({ request }) => {
    if (request.mode === 'navigate') {
        return (await matchPrecache('/offline.html')) ?? Response.error();
    }
    return Response.error();
});

async function avisarClientes(mensagem) {
    const clientes = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    clientes.forEach((c) => c.postMessage(mensagem));
}

self.addEventListener('sync', (evento) => {
    if (evento.tag === 'dem-sincronizar') {
        evento.waitUntil(
            sincronizar()
                .then((resultado) => avisarClientes({ tipo: 'sincronizacao-concluida', resultado }))
                .catch(async (erro) => {
                    await avisarClientes({ tipo: 'sincronizacao-erro', mensagem: erro.message });
                    throw erro; // rejeitar faz o navegador tentar de novo mais tarde
                }),
        );
    }
});

self.addEventListener('message', (evento) => {
    if (evento.data?.tipo === 'limpar-cache') {
        evento.waitUntil(caches.keys().then((nomes) => Promise.all(nomes.map((n) => caches.delete(n)))));
    }
});
