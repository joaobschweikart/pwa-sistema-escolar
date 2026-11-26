import { defineConfig, minimal2023Preset } from '@vite-pwa/assets-generator/config';

// Gera os ícones da PWA (64, 192, 512, maskable 512 e apple-touch 180) a partir do SVG
export default defineConfig({
    headLinkOptions: { preset: '2023' },
    preset: {
        ...minimal2023Preset,
        maskable: { ...minimal2023Preset.maskable, resizeOptions: { background: '#0f766e' } },
        apple: { ...minimal2023Preset.apple, resizeOptions: { background: '#0f766e' } },
    },
    images: ['public/icons/icon.svg'],
});
