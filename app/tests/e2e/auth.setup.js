import { test as setup, expect } from '@playwright/test';
import { entrar, ESTADO_PROFESSOR, ESTADO_GESTOR } from './apoio.js';

setup('login do professor abre a PWA do diário', async ({ page }) => {
    await entrar(page, 'professor@dem.local');
    await page.waitForURL('**/professor');
    await expect(page.getByTestId('app-professor')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Minhas turmas' })).toBeVisible();
    await expect(page.getByTestId('status-sync')).toHaveText('Sincronizado');
    await expect(page.getByTestId('cartao-turma')).toHaveCount(3);
    await page.context().storageState({ path: ESTADO_PROFESSOR });
});

setup('login do gestor abre o painel', async ({ page }) => {
    await entrar(page, 'gestor@dem.local');
    await page.waitForURL('**/painel');
    await expect(page.getByRole('heading', { name: /Olá, Carlos/ })).toBeVisible();
    await page.context().storageState({ path: ESTADO_GESTOR });
});
