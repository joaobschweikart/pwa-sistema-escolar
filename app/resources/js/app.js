/**
 * pwa.ui — script comum a todas as páginas: Alpine.js, registro do service
 * worker, indicador de conexão e limpeza dos dados locais no logout.
 */
import Alpine from 'alpinejs';
import { limparTudo } from './offline/db.js';

window.Alpine = Alpine;

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/build/sw.js', { scope: '/' }).catch((e) => console.warn('[DEM] SW não registrado', e));
    });
}

// Indicador global de conexão (usado nas telas de gestão)
document.addEventListener('alpine:init', () => {
    Alpine.store('conexao', {
        online: navigator.onLine,
        init() {
            window.addEventListener('online', () => (this.online = true));
            window.addEventListener('offline', () => (this.online = false));
        },
    });
});

// Logout: apaga IndexedDB e caches antes de encerrar a sessão no servidor
document.addEventListener('submit', async (evento) => {
    const form = evento.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-logout') || form.dataset.limpo) return;
    evento.preventDefault();
    try {
        await limparTudo();
    } finally {
        form.dataset.limpo = '1';
        form.submit();
    }
});

// Alpine inicia depois que todos os módulos da página registraram seus componentes
document.addEventListener('DOMContentLoaded', () => Alpine.start());
