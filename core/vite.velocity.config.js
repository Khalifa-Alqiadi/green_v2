import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';

const entry = 'resources/themes/velocity/js/app.js';
const developmentOrigin = process.env.VITE_VELOCITY_ORIGIN;
const developmentPort = Number.parseInt(process.env.VITE_VELOCITY_PORT ?? '5174', 10);

export default defineConfig({
    plugins: [
        laravel({
            input: entry,
            buildDirectory: 'build/velocity',
            hotFile: 'storage/vite.velocity.hot',
            refresh: [
                'resources/views/themes/velocity/**/*.blade.php',
                'resources/themes/velocity/**/*',
            ],
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
        host: process.env.VITE_VELOCITY_HOST ?? '127.0.0.1',
        port: Number.isNaN(developmentPort) ? 5174 : developmentPort,
        strictPort: true,
        ...(developmentOrigin ? { origin: developmentOrigin } : {}),
    },
    build: {
        target: 'es2022',
        sourcemap: false,
        manifest: 'manifest.json',
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
    test: {
        environment: 'jsdom',
        include: ['tests/Frontend/velocity/**/*.test.js'],
        restoreMocks: true,
    },
});
