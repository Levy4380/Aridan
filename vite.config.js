import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { loadEnv } from 'vite';
import { defineConfig, lazyPlugins } from 'vite-plus';

// To open the dev site from another device (a phone on the same network),
// set VITE_DEV_HOST to this machine's LAN IP in .env: Vite then listens on
// the network and the page asks for its assets there instead of localhost.
const devHost = loadEnv('development', process.cwd(), '').VITE_DEV_HOST;

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
            fonts: [
                bunny('Space Grotesk', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        ...(devHost ? { host: '0.0.0.0', hmr: { host: devHost } } : {}),
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
