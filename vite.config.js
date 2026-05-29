import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
const path = require('path');


export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    if (command === 'build' && !process.env.ASSET_URL) {
        // Production assets are served from /public in this project, but we
        // keep the prefix build-only to avoid changing Laravel's runtime asset().
        process.env.ASSET_URL = env.VITE_BUILD_ASSET_URL || '/public';
    }

    return {
        plugins: [
            laravel({
                input: [
                    'resources/scss/app.scss',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
        ],
        resolve: {
            alias: {
                '~resources': '/resources/',
                '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
            }
        },
    };
});
