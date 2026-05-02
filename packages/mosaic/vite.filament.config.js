import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig(async () => {
    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/admin/capell-mosaic-filament.css',
                    'resources/js/admin/layout-builder.js',
                ],
                publicDirectory: 'publishes',
                refresh: false,
            }),
        ],
        server: {
            open: false,
        },
        build: {
            manifest: false,
            outDir: './publishes/build/admin',
            assetsInlineLimit: 0,
            rollupOptions: {
                preserveEntrySignatures: 'strict',
                output: {
                    entryFileNames: '[name].js',
                    chunkFileNames: '[name].js',
                    assetFileNames: '[name].[ext]',
                },
                treeshake: {
                    moduleSideEffects: (id) => {
                        // Prevent tree-shaking for layout-builder.js
                        return id.includes('layout-builder.js')
                    },
                },
            },
        },
    }
})
