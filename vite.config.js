import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig(async () => {
    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/capell-layout.css',
                    'resources/js/capell-layout.js',
                ],
                publicDirectory: 'publishes',
                refresh: false,
            }),
            tailwindcss(),
        ],
        server: {
            open: false,
        },
        build: {
            outDir: './resources/dist',
        },
    }
})
