import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import getGitCommitHash from './resources/js/getGitCommitHash';

export default defineConfig({
    define: {
        gitHash: JSON.stringify(getGitCommitHash())
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/app-lite.css',
                'resources/css/font.css',
                'resources/js/app.js',
                'resources/js/kao-logic.js'
            ],
            refresh: true,
        }),
    ],
    server: {
        host: true,
        hmr: {
            host: '192.168.0.2'
        },
    },
});
