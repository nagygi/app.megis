import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Geologica is the single typeface of the mégis identity (_ds/tokens/fonts.css).
                // latin-ext carries ő and ű.
                bunny('Geologica', {
                    weights: [400, 500, 600, 800],
                    subsets: ['latin', 'latin-ext'],
                    // Only the body weight is preloaded; the others load on first use.
                    preload: [{ weight: 400 }],
                    fallbacks: ['ui-sans-serif', 'system-ui', 'sans-serif'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
