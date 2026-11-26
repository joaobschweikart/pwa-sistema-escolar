import { test, expect } from '@playwright/test';
import { consultar, aguardarOfflinePronto, ESTADO_PROFESSOR } from './apoio.js';

test.use({ storageState: ESTADO_PROFESSOR });

test('professor registra aula, frequência e nota offline e sincroniza ao voltar a conexão', async ({ page, context }, info) => {
    const conteudo = `E2E ${info.project.name} ${Date.now()}`;
    const nota = (Math.floor(Math.random() * 90) / 10 + 1).toFixed(1); // 1,0 a 9,9
    const status = page.getByTestId('status-sync');

    await page.goto('/professor');
    await expect(status).toHaveText('Sincronizado');
    await expect(page.getByTestId('cartao-turma').filter({ hasText: '5º ano A · Matemática' })).toBeVisible();
    await aguardarOfflinePronto(page);

    // ---- fica offline ----
    await context.setOffline(true);
    await expect(page.getByTestId('aviso-offline')).toBeVisible();
    await expect(status).toHaveText('Offline');

    // ---- aula + frequência (aluno nº 02 com falta) ----
    await page.getByTestId('cartao-turma').filter({ hasText: '5º ano A · Matemática' }).click();
    await expect(page.getByTestId('titulo-turma')).toHaveText('5º ano A · Matemática');
    await page.getByTestId('botao-nova-aula').click();
    await page.getByTestId('campo-conteudo').fill(conteudo);
    const segundoAluno = page.getByTestId('botao-presenca').nth(1);
    await segundoAluno.click();
    await expect(segundoAluno).toHaveText('Falta');
    await page.getByTestId('botao-salvar-aula').click();
    await expect(page.getByTestId('item-aula').filter({ hasText: conteudo })).toContainText('pendente');
    await expect(status).toHaveText('Offline · 2 pendente(s)');

    // ---- nota ----
    await page.getByTestId('aba-notas').click();
    await page.getByTestId('item-avaliacao').filter({ hasText: 'Trabalho em grupo' }).click();
    await page.getByTestId('campo-nota').first().fill(nota);
    await page.getByTestId('botao-salvar-notas').click();
    await expect(status).toHaveText('Offline · 3 pendente(s)');

    // Nada chegou ao servidor ainda
    expect(await consultar('SELECT id FROM aulas WHERE conteudo = ?', [conteudo])).toHaveLength(0);

    // ---- recarrega a página offline: o app abre a partir do cache ----
    await page.reload();
    await expect(page.getByTestId('app-professor')).toBeVisible();
    // a rede está realmente bloqueada (inclusive para o service worker): a página veio do cache
    expect(await page.evaluate(() => fetch('/api/health').then(() => 'rede', () => 'sem-rede'))).toBe('sem-rede');
    expect(await page.evaluate(() => navigator.serviceWorker.controller !== null)).toBe(true);
    await expect(page.getByTestId('aviso-offline')).toBeVisible();
    await expect(status).toHaveText('Offline · 3 pendente(s)');
    await page.getByTestId('aba-aulas').click();
    await expect(page.getByTestId('item-aula').filter({ hasText: conteudo })).toBeVisible();

    // ---- volta online: sincroniza sozinho (evento "online") ----
    await context.setOffline(false);
    await expect(status).toHaveText('Sincronizado', { timeout: 30_000 });

    // ---- verifica no banco ----
    const [aula] = await consultar('SELECT id, versao FROM aulas WHERE conteudo = ?', [conteudo]);
    expect(aula).toBeTruthy();
    const frequencias = await consultar(
        `SELECT m.numero_chamada, f.presente FROM frequencias f JOIN matriculas m ON m.id = f.matricula_id
         WHERE f.aula_id = ? ORDER BY m.numero_chamada`,
        [aula.id],
    );
    expect(frequencias).toHaveLength(12);
    expect(frequencias[0].presente).toBe(1);
    expect(frequencias[1].presente).toBe(0);

    const [lancada] = await consultar(
        `SELECT n.valor FROM notas n JOIN avaliacoes a ON a.id = n.avaliacao_id JOIN matriculas m ON m.id = n.matricula_id
         WHERE a.titulo = 'Trabalho em grupo' AND m.numero_chamada = 1`,
    );
    expect(Number(lancada.valor)).toBe(Number(nota));

    const [auditoria] = await consultar(
        `SELECT au.origem, au.valor_novo FROM auditoria_notas au JOIN avaliacoes a ON a.id = au.avaliacao_id
         WHERE a.titulo = 'Trabalho em grupo' ORDER BY au.id DESC LIMIT 1`,
    );
    expect(auditoria.origem).toBe('sincronizacao');
    expect(Number(auditoria.valor_novo)).toBe(Number(nota));
});

test('start_url da PWA abre sem conexão', async ({ page, context }) => {
    await page.goto('/professor');
    await expect(page.getByTestId('cartao-turma').first()).toBeVisible();
    await aguardarOfflinePronto(page);
    await context.setOffline(true);
    await page.goto('/professor?origem=pwa');
    await expect(page.getByTestId('app-professor')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Minhas turmas' })).toBeVisible();
    await context.setOffline(false);
});
