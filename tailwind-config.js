/**
 * Capell Mosaic - Tailwind Configuration
 *
 * Import this in your tailwind.config.js to use Mosaic design tokens:
 *
 * const mosaicTheme = require('./packages/mosaic/tailwind-config.js');
 *
 * module.exports = {
 *   theme: {
 *     extend: mosaicTheme.theme,
 *   },
 * };
 */

module.exports = {
    theme: {
        extend: {
            colors: {
                mosaic: {
                    // Primary
                    primary: 'var(--mosaic-primary)',
                    'primary-container': 'var(--mosaic-primary-container)',
                    'on-primary': 'var(--mosaic-on-primary)',
                    'on-primary-container':
                        'var(--mosaic-on-primary-container)',

                    // Secondary
                    secondary: 'var(--mosaic-secondary)',
                    'secondary-container': 'var(--mosaic-secondary-container)',
                    'on-secondary': 'var(--mosaic-on-secondary)',
                    'on-secondary-container':
                        'var(--mosaic-on-secondary-container)',

                    // Tertiary (Gold)
                    tertiary: 'var(--mosaic-tertiary)',
                    'tertiary-container': 'var(--mosaic-tertiary-container)',
                    'on-tertiary': 'var(--mosaic-on-tertiary)',
                    'on-tertiary-container':
                        'var(--mosaic-on-tertiary-container)',

                    // Surfaces
                    background: 'var(--mosaic-background)',
                    surface: 'var(--mosaic-surface)',
                    'surface-dim': 'var(--mosaic-surface-dim)',
                    'surface-bright': 'var(--mosaic-surface-bright)',
                    'surface-container-lowest':
                        'var(--mosaic-surface-container-lowest)',
                    'surface-container-low':
                        'var(--mosaic-surface-container-low)',
                    'surface-container': 'var(--mosaic-surface-container)',
                    'surface-container-high':
                        'var(--mosaic-surface-container-high)',
                    'surface-container-highest':
                        'var(--mosaic-surface-container-highest)',
                    'surface-variant': 'var(--mosaic-surface-variant)',

                    // Text
                    'on-surface': 'var(--mosaic-on-surface)',
                    'on-surface-variant': 'var(--mosaic-on-surface-variant)',

                    // Semantic
                    error: 'var(--mosaic-error)',
                    'error-container': 'var(--mosaic-error-container)',
                    'on-error': 'var(--mosaic-on-error)',
                    'on-error-container': 'var(--mosaic-on-error-container)',

                    success: 'var(--mosaic-success)',
                    'success-container': 'var(--mosaic-success-container)',
                    'on-success': 'var(--mosaic-on-success)',
                    'on-success-container':
                        'var(--mosaic-on-success-container)',

                    warning: 'var(--mosaic-warning)',
                    'warning-container': 'var(--mosaic-warning-container)',
                    'on-warning': 'var(--mosaic-on-warning)',
                    'on-warning-container':
                        'var(--mosaic-on-warning-container)',

                    // Outline
                    outline: 'var(--mosaic-outline)',
                    'outline-variant': 'var(--mosaic-outline-variant)',
                },
            },

            spacing: {
                'mosaic-xs': 'var(--mosaic-spacing-xs)',
                'mosaic-sm': 'var(--mosaic-spacing-sm)',
                'mosaic-md': 'var(--mosaic-spacing-md)',
                'mosaic-lg': 'var(--mosaic-spacing-lg)',
                'mosaic-xl': 'var(--mosaic-spacing-xl)',
                'mosaic-2xl': 'var(--mosaic-spacing-2xl)',
                'mosaic-3xl': 'var(--mosaic-spacing-3xl)',
            },

            fontFamily: {
                'mosaic-headline': 'var(--mosaic-font-headline)',
                'mosaic-body': 'var(--mosaic-font-body)',
                'mosaic-mono': 'var(--mosaic-font-mono)',
            },

            fontSize: {
                'mosaic-display-lg': 'var(--mosaic-text-display-lg)',
                'mosaic-display-md': 'var(--mosaic-text-display-md)',
                'mosaic-headline-lg': 'var(--mosaic-text-headline-lg)',
                'mosaic-headline-md': 'var(--mosaic-text-headline-md)',
                'mosaic-headline-sm': 'var(--mosaic-text-headline-sm)',
                'mosaic-title-lg': 'var(--mosaic-text-title-lg)',
                'mosaic-title-md': 'var(--mosaic-text-title-md)',
                'mosaic-title-sm': 'var(--mosaic-text-title-sm)',
                'mosaic-body-lg': 'var(--mosaic-text-body-lg)',
                'mosaic-body-md': 'var(--mosaic-text-body-md)',
                'mosaic-body-sm': 'var(--mosaic-text-body-sm)',
                'mosaic-label-lg': 'var(--mosaic-text-label-lg)',
                'mosaic-label-md': 'var(--mosaic-text-label-md)',
                'mosaic-label-sm': 'var(--mosaic-text-label-sm)',
            },

            borderRadius: {
                'mosaic-sm': 'var(--mosaic-radius-sm)',
                'mosaic-md': 'var(--mosaic-radius-md)',
                'mosaic-lg': 'var(--mosaic-radius-lg)',
                'mosaic-xl': 'var(--mosaic-radius-xl)',
                'mosaic-full': 'var(--mosaic-radius-full)',
            },

            transitionDuration: {
                'mosaic-fast': 'var(--mosaic-transition-fast)',
                'mosaic-base': 'var(--mosaic-transition-base)',
                'mosaic-slow': 'var(--mosaic-transition-slow)',
            },

            backdropBlur: {
                'mosaic-sm': 'var(--mosaic-blur-sm)',
                'mosaic-md': 'var(--mosaic-blur-md)',
                'mosaic-lg': 'var(--mosaic-blur-lg)',
                'mosaic-xl': 'var(--mosaic-blur-xl)',
            },

            boxShadow: {
                'mosaic-ambient': '0 12px 32px var(--mosaic-shadow-ambient)',
                'mosaic-lg': '0 10px 25px rgba(0, 0, 0, 0.3)',
            },

            backgroundImage: {
                'mosaic-primary-gradient':
                    'linear-gradient(135deg, var(--mosaic-primary-container) 0%, #5a00c6 100%)',
                'mosaic-secondary-gradient':
                    'linear-gradient(135deg, var(--mosaic-secondary-container) 0%, #3131c0 100%)',
                'mosaic-tertiary-gradient':
                    'linear-gradient(135deg, var(--mosaic-tertiary) 0%, var(--mosaic-tertiary-container) 100%)',
            },
        },
    },

    plugins: [],
}
