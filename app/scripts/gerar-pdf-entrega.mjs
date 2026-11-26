// Gera doc/entrega-2.pdf a partir de doc/infraestrutura.md (capa + descrição da infraestrutura).
// Uso (a partir de app/):  npm run pdf   (gera um HTML temporário em storage/)
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { marked } from 'marked';
import { chromium } from '@playwright/test';

const DOC = resolve(dirname(fileURLToPath(import.meta.url)), '../../doc');
const md = readFileSync(resolve(DOC, 'infraestrutura.md'), 'utf8')
    .replace(/^# .*\n/, '')                                  // o título vai na capa
    .replace(/^\*\*Práticas Extensionistas IV[\s\S]*?\n\n/m, ''); // cabeçalho repetido da capa

const corpo = marked.parse(md);
const html = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<base href="${pathToFileURL(DOC + '/').href}">
<style>
  @page { size: A4; margin: 18mm 16mm 18mm 16mm; }
  body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9.6pt; color: #0f172a; line-height: 1.45; }
  .capa { height: 255mm; display: flex; flex-direction: column; justify-content: space-between; page-break-after: always; text-align: center; }
  .capa h1 { font-size: 21pt; margin: 0 0 6mm; color: #115e59; }
  .capa .sub { font-size: 12pt; color: #334155; }
  .capa dl { text-align: left; margin: 0 auto; font-size: 11pt; border-top: 2px solid #0f766e; padding-top: 6mm; width: 150mm; }
  .capa dt { font-weight: bold; margin-top: 4mm; } .capa dd { margin: 1mm 0 0; }
  h2 { color: #115e59; font-size: 13.5pt; border-bottom: 1px solid #99f6e4; padding-bottom: 1mm; margin-top: 7mm; page-break-after: avoid; }
  h3 { font-size: 11pt; margin-top: 5mm; page-break-after: avoid; }
  table { border-collapse: collapse; width: 100%; margin: 3mm 0; font-size: 8.4pt; page-break-inside: auto; }
  tr { page-break-inside: avoid; }
  th, td { border: 1px solid #cbd5e1; padding: 1.4mm 2mm; vertical-align: top; text-align: left; }
  th { background: #f0fdfa; }
  code { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.2pt; background: #f1f5f9; padding: 0 1mm; border-radius: 1mm; }
  pre { background: #f8fafc; border: 1px solid #e2e8f0; padding: 3mm; font-size: 7.8pt; white-space: pre-wrap; page-break-inside: avoid; }
  pre code { background: none; padding: 0; }
  img { max-width: 100%; max-height: 200mm; display: block; margin: 3mm auto; page-break-inside: avoid; }
  hr { border: 0; margin: 2mm 0; }
  a { color: #0f766e; }
</style></head><body>
<section class="capa">
  <div>
    <p class="sub">UNIVERSIDADE DO OESTE DE SANTA CATARINA — UNOESC<br>Curso de Análise e Desenvolvimento de Sistemas</p>
  </div>
  <div>
    <p class="sub">Práticas Extensionistas IV — Entrega 2</p>
    <h1>Diário Escolar Municipal (DEM)<br>Configuração da infraestrutura em nuvem</h1>
    <p class="sub">PWA offline-first, API REST e infraestrutura AWS como código</p>
  </div>
  <dl>
    <dt>Integrante</dt><dd>João Vitor Bernardon Schweikart</dd>
    <dt>Link do Git da Prática Extensionista IV</dt><dd><a href="https://github.com/joaobschweikart/pwa-sistema-escolar">https://github.com/joaobschweikart/pwa-sistema-escolar</a></dd>
    <dt>Infraestrutura escolhida</dt><dd>Cloud pública — Amazon Web Services, região sa-east-1 (São Paulo): Amazon Lightsail + Docker Compose, Amazon ECR, AWS Systems Manager, Amazon CloudWatch, Amazon SES e Lightsail Object Storage</dd>
  </dl>
  <p class="sub">Chapecó — SC, ${new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })}</p>
</section>
${corpo}
</body></html>`;

const arquivoHtml = resolve(DOC, '.entrega-2.html');
writeFileSync(arquivoHtml, html);
const browser = await chromium.launch();
const page = await browser.newPage();
await page.goto(pathToFileURL(arquivoHtml).href, { waitUntil: 'networkidle' });
await page.pdf({
    path: resolve(DOC, 'entrega-2.pdf'),
    format: 'A4',
    printBackground: true,
    displayHeaderFooter: true,
    headerTemplate: '<span></span>',
    footerTemplate: '<div style="font-size:7pt;width:100%;text-align:center;color:#64748b">DEM — Entrega 2 · João Vitor Bernardon Schweikart · <span class="pageNumber"></span>/<span class="totalPages"></span></div>',
    margin: { top: '16mm', bottom: '18mm', left: '16mm', right: '16mm' },
});
await browser.close();
console.log('gerado doc/entrega-2.pdf');
