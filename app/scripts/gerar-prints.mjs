// Gera os prints do README (com o ambiente local no ar):  npm run prints
import { chromium, devices } from '@playwright/test';

const BASE = process.env.DEM_BASE_URL ?? 'http://localhost:8080';
const SAIDA = process.env.DEM_PRINTS ?? '../doc/prints';

async function sessao(browser, opcoes, email) {
    const ctx = await browser.newContext({ ...opcoes, locale: 'pt-BR', timezoneId: 'America/Sao_Paulo' });
    const page = await ctx.newPage();
    await page.goto(`${BASE}/login`);
    await page.screenshot({ path: `${SAIDA}/${opcoes.nome}-login.png` });
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha').fill('dem@2026');
    await page.getByRole('button', { name: 'Entrar' }).click();
    await page.waitForLoadState('networkidle');
    return { ctx, page };
}

const browser = await chromium.launch();
for (const [nome, opcoes] of [['mobile', devices['Pixel 7']], ['desktop', { viewport: { width: 1366, height: 860 } }]]) {
    const o = { ...opcoes, nome };
    // Professor
    let { ctx, page } = await sessao(browser, o, 'professor@dem.local');
    await page.getByTestId('cartao-turma').first().waitFor();
    await page.screenshot({ path: `${SAIDA}/${nome}-professor-turmas.png` });
    await page.getByTestId('cartao-turma').filter({ hasText: '5º ano A · Matemática' }).click();
    await page.screenshot({ path: `${SAIDA}/${nome}-professor-aulas.png` });
    await ctx.setOffline(true);
    await page.getByTestId('botao-nova-aula').click();
    await page.getByTestId('campo-conteudo').fill('Frações: exercícios de revisão');
    await page.getByTestId('botao-presenca').nth(2).click();
    await page.screenshot({ path: `${SAIDA}/${nome}-professor-chamada-offline.png` });
    await page.getByTestId('botao-salvar-aula').click();
    await page.waitForTimeout(400);
    await page.screenshot({ path: `${SAIDA}/${nome}-professor-pendente-offline.png` });
    await page.getByTestId('aba-boletim').click();
    await page.screenshot({ path: `${SAIDA}/${nome}-professor-boletim.png` });
    await ctx.setOffline(false);
    await page.getByTestId('status-sync').filter({ hasText: 'Sincronizado' }).waitFor({ timeout: 20000 });
    await ctx.close();
    // Gestor
    ({ ctx, page } = await sessao(browser, o, 'gestor@dem.local'));
    await page.screenshot({ path: `${SAIDA}/${nome}-gestor-painel.png` });
    await page.goto(`${BASE}/gestao/turmas`);
    await page.screenshot({ path: `${SAIDA}/${nome}-gestor-turmas.png` });
    await page.goto(`${BASE}/gestao/conflitos?status=resolvido`);
    await page.goto(`${BASE}/gestao/conflitos`);
    await page.screenshot({ path: `${SAIDA}/${nome}-gestor-conflitos.png` });
    await ctx.close();
}
await browser.close();
console.log('prints gerados em', SAIDA);
