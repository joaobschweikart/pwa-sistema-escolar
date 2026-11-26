import mysql from 'mysql2/promise';

export const SENHA = 'dem@2026';
export const ESTADO_PROFESSOR = 'tests/e2e/.auth/professor.json';
export const ESTADO_GESTOR = 'tests/e2e/.auth/gestor.json';

/** Consulta direta ao banco dem_db do ambiente local (verificação da sincronização). */
export async function consultar(sql, parametros = []) {
    const conexao = await mysql.createConnection({
        host: process.env.DEM_DB_HOST ?? '127.0.0.1',
        port: Number(process.env.DEM_DB_PORT ?? 3308),
        user: process.env.DEM_DB_USER ?? 'dem',
        password: process.env.DEM_DB_PASSWORD ?? 'dem_dev_password',
        database: process.env.DEM_DB_NAME ?? 'dem_db',
    });
    try {
        const [linhas] = await conexao.execute(sql, parametros);
        return linhas;
    } finally {
        await conexao.end();
    }
}

export async function entrar(page, email) {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill(email);
    await page.getByLabel('Senha').fill(SENHA);
    await page.getByRole('button', { name: 'Entrar' }).click();
}

/** Aguarda o service worker controlar a página e o app shell estar em cache. */
export async function aguardarOfflinePronto(page) {
    await page.evaluate(() => navigator.serviceWorker.ready);
    await page.waitForFunction(() => navigator.serviceWorker.controller !== null, null, { timeout: 15_000 });
    await page.waitForFunction(async () => Boolean(await caches.match('/professor')), null, { timeout: 15_000, polling: 500 });
}
