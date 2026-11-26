import { test, expect } from '@playwright/test';
import { ESTADO_PROFESSOR } from './apoio.js';

test.use({ storageState: ESTADO_PROFESSOR });

test('critérios de instalabilidade: manifest, ícones e service worker', async ({ page, request }) => {
    await page.goto('/professor');

    const href = await page.locator('link[rel="manifest"]').getAttribute('href');
    const resposta = await request.get(href);
    expect(resposta.ok()).toBeTruthy();
    const manifest = await resposta.json();

    expect(manifest).toMatchObject({ name: 'Diário Escolar Municipal', short_name: 'Diário DEM', display: 'standalone', scope: '/', lang: 'pt-BR' });
    expect(manifest.start_url).toContain('/professor');
    expect(manifest.theme_color).toBeTruthy();

    const icones = manifest.icons;
    expect(icones.some((i) => i.sizes === '192x192')).toBeTruthy();
    expect(icones.some((i) => i.sizes === '512x512' && (i.purpose ?? 'any') === 'any')).toBeTruthy();
    expect(icones.some((i) => i.purpose === 'maskable')).toBeTruthy();
    for (const icone of icones) {
        const r = await request.get(icone.src);
        expect(r.ok(), icone.src).toBeTruthy();
        expect(r.headers()['content-type']).toContain('image/png');
    }

    const sw = await page.evaluate(async () => {
        const reg = await navigator.serviceWorker.ready;
        return { scope: reg.scope, estado: reg.active?.state, url: reg.active?.scriptURL };
    });
    expect(sw.estado).toBe('activated');
    expect(new URL(sw.scope).pathname).toBe('/');
    expect(sw.url).toContain('/build/sw.js');

    const swHttp = await request.get('/build/sw.js');
    expect(swHttp.headers()['service-worker-allowed']).toBe('/');
});

test('logout limpa o cache e o IndexedDB', async ({ browser }, info) => {
    test.skip(info.project.name !== 'desktop-chrome', 'executado uma vez');
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('professor2@dem.local');
    await page.getByLabel('Senha').fill('dem@2026');
    await page.getByRole('button', { name: 'Entrar' }).click();
    await page.waitForURL('**/professor');
    await expect(page.getByTestId('status-sync')).toHaveText('Sincronizado');
    await expect(page.getByTestId('cartao-turma').first()).toBeVisible();
    await page.waitForFunction(async () => Boolean(await caches.match('/professor')), null, { polling: 500 });

    expect(await page.evaluate(async () => (await indexedDB.databases()).map((d) => d.name))).toContain('dem-offline');

    page.on('dialog', (d) => d.accept());
    await page.getByRole('button', { name: 'Sair' }).first().click();
    await page.waitForURL('**/login');

    const restante = await page.evaluate(async () => ({
        bancos: (await indexedDB.databases()).map((d) => d.name),
        shell: Boolean(await caches.match('/professor')),
    }));
    expect(restante.bancos).not.toContain('dem-offline');
    expect(restante.shell).toBe(false);
    await context.close();
});
