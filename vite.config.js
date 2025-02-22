import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/app-lite.css",
                "resources/css/font.css",
                "resources/js/app.js",
                "resources/js/kao-logic.js",
            ],
            refresh: true,
        }),
    ],
    server: {
        cors: true,
        host: true,
        hmr: {
            host: "192.168.0.2",
        },
    },
});
