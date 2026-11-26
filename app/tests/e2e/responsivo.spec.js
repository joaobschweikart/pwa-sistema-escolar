import { test, expect } from '@playwright/test';
import { ESTADO_GESTOR, ESTADO_PROFESSOR } from './apoio.js';

test.describe('gestor', () => {
    test.use({ storageState: ESTADO_GESTOR });

    test('navegação e listas se adaptam ao tamanho da tela', async ({ page, isMobile }) => {
        await page.goto('/gestao/alunos');
        const navInferior = page.getByRole('navigation', { name: 'Navegação inferior' });
        const navLateral = page.getByRole('navigation', { name: 'Navegação principal' });
        const tabela = page.locator('table.tabela');
        const cards = page.locator('ul.md\\:hidden > li');

        if (isMobile) {
            await expect(navInferior).toBeVisible();
            await expect(navLateral).toBeHidden();
            await expect(tabela).toBeHidden();
            await expect(cards.first()).toBeVisible();
            // alvos de toque ≥ 44 px
            for (const link of await navInferior.locator('a, button').all()) {
                const caixa = await link.boundingBox();
                expect(caixa.height).toBeGreaterThanOrEqual(44);
            }
        } else {
            await expect(navInferior).toBeHidden();
            await expect(navLateral).toBeVisible();
            await expect(tabela).toBeVisible();
            await expect(cards.first()).toBeHidden();
        }
        await page.screenshot({ path: `tests/e2e/test-results/gestor-alunos-${isMobile ? 'mobile' : 'desktop'}.png`, fullPage: true });
    });
});

test.describe('professor', () => {
    test.use({ storageState: ESTADO_PROFESSOR });

    test('diário com navegação inferior no celular', async ({ page, isMobile }) => {
        await page.goto('/professor');
        await expect(page.getByTestId('status-sync')).toHaveText('Sincronizado');
        const navInferior = page.getByRole('navigation', { name: 'Navegação inferior' });
        if (isMobile) {
            await expect(navInferior).toBeVisible();
        } else {
            await expect(navInferior).toBeHidden();
        }
        await page.getByTestId('cartao-turma').filter({ hasText: '5º ano A · Matemática' }).click();
        await page.getByTestId('botao-nova-aula').click();
        for (const botao of (await page.getByTestId('botao-presenca').all()).slice(0, 3)) {
            const caixa = await botao.boundingBox();
            expect(caixa.height).toBeGreaterThanOrEqual(44);
        }
        await page.screenshot({ path: `tests/e2e/test-results/professor-chamada-${isMobile ? 'mobile' : 'desktop'}.png`, fullPage: false });
    });
});
