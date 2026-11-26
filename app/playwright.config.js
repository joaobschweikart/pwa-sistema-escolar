import { defineConfig, devices } from '@playwright/test';

/**
 * E2E do DEM. Pré-requisito: ambiente local no ar (docker compose up -d).
 *   npm run e2e            → todos os projetos (desktop e celular)
 *   npm run e2e:docker     → mesmo teste dentro da imagem oficial do Playwright
 */
export default defineConfig({
    testDir: './tests/e2e',
    outputDir: './tests/e2e/test-results',
    timeout: 90_000,
    expect: { timeout: 10_000 },
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]],
    use: {
        baseURL: process.env.DEM_BASE_URL ?? 'http://localhost:8080',
        locale: 'pt-BR',
        timezoneId: 'America/Sao_Paulo',
        serviceWorkers: 'allow',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/ },
        {
            name: 'desktop-chrome',
            use: { ...devices['Desktop Chrome'], viewport: { width: 1366, height: 860 } },
            dependencies: ['setup'],
        },
        {
            name: 'mobile-chrome',
            use: { ...devices['Pixel 7'] },
            dependencies: ['setup'],
        },
    ],
});
