import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/kao-logic.js'],
            refresh: true,
        }),
    ],
    server: {
        host: true,
        hmr: {
            host: 'localhost'
        },
    }
});
