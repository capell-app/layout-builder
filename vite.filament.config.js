import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from 'tailwindcss-v3'
import autoprefixer from 'autoprefixer'
import { exec } from 'child_process'

export default defineConfig(async () => {
    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/capell-layout-filament.css',
                    'resources/js/components/layout-builder.js',
                ],
                publicDirectory: 'publishes',
                refresh: false,
            }),
            {
                name: 'filament-purge',
                buildEnd() {
                    exec('npm run filament-purge', (error, stdout, stderr) => {
                        if (error) {
                            console.error(
                                `Error running filament-purge: ${error.message}`,
                            )
                            return
                        }
                        if (stderr) {
                            console.error(`filament-purge stderr: ${stderr}`)
                        }
                        console.log(`filament-purge stdout: ${stdout}`)
                    })
                },
            },
        ],
        css: {
            postcss: {
                plugins: [
                    tailwindcss({
                        config: './tailwind.filament.config.js',
                    }),
                    autoprefixer(),
                ],
            },
        },
        server: {
            open: false,
        },
        build: {
            manifest: false,
            outDir: './publishes/build',
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
