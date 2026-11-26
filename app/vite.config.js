import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';

// Revisão (hash) de arquivos servidos pelo Laravel em public/ que também vão para o precache
const revisao = (arquivo) => createHash('md5').update(readFileSync(arquivo)).digest('hex');

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/professor/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        VitePWA({
            // Service worker próprio (Workbox) para controlar app shell, fila e Background Sync
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            injectRegister: false,
            manifestFilename: 'manifest.webmanifest',
            scope: '/',
            base: '/',
            buildBase: '/build/',
            injectManifest: {
                globPatterns: ['**/*.{js,css,woff2,svg,png,webmanifest}'],
                additionalManifestEntries: [
                    { url: '/offline.html', revision: revisao('public/offline.html') },
                    { url: '/icons/pwa-192x192.png', revision: revisao('public/icons/pwa-192x192.png') },
                    { url: '/icons/icon.svg', revision: revisao('public/icons/icon.svg') },
                ],
            },
            manifest: {
                id: '/professor',
                name: 'Diário Escolar Municipal',
                short_name: 'Diário DEM',
                description: 'Diário de classe, frequência e notas — funciona mesmo sem internet.',
                lang: 'pt-BR',
                dir: 'ltr',
                start_url: '/professor?origem=pwa',
                scope: '/',
                display: 'standalone',
                orientation: 'any',
                background_color: '#f8fafc',
                theme_color: '#0f766e',
                categories: ['education', 'productivity'],
                icons: [
                    { src: '/icons/pwa-64x64.png', sizes: '64x64', type: 'image/png' },
                    { src: '/icons/pwa-192x192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/pwa-512x512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/maskable-icon-512x512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
                shortcuts: [
                    { name: 'Minhas turmas', url: '/professor?origem=atalho', icons: [{ src: '/icons/pwa-192x192.png', sizes: '192x192' }] },
                ],
            },
            devOptions: { enabled: false },
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
