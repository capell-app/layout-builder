/**
 * Capell LayoutBuilder - Tailwind Configuration
 *
 * Import this in your tailwind.config.js to use LayoutBuilder design tokens:
 *
 * const layout-builderTheme = require('./packages/layout-builder/tailwind-config.js');
 *
 * module.exports = {
 *   theme: {
 *     extend: layout-builderTheme.theme,
 *   },
 * };
 */

module.exports = {
    theme: {
        extend: {
            colors: {
                layout-builder: {
                    // Primary
                    primary: 'var(--layout-builder-primary)',
                    'primary-container': 'var(--layout-builder-primary-container)',
                    'on-primary': 'var(--layout-builder-on-primary)',
                    'on-primary-container':
                        'var(--layout-builder-on-primary-container)',

                    // Secondary
                    secondary: 'var(--layout-builder-secondary)',
                    'secondary-container': 'var(--layout-builder-secondary-container)',
                    'on-secondary': 'var(--layout-builder-on-secondary)',
                    'on-secondary-container':
                        'var(--layout-builder-on-secondary-container)',

                    // Tertiary (Gold)
                    tertiary: 'var(--layout-builder-tertiary)',
                    'tertiary-container': 'var(--layout-builder-tertiary-container)',
                    'on-tertiary': 'var(--layout-builder-on-tertiary)',
                    'on-tertiary-container':
                        'var(--layout-builder-on-tertiary-container)',

                    // Surfaces
                    background: 'var(--layout-builder-background)',
                    surface: 'var(--layout-builder-surface)',
                    'surface-dim': 'var(--layout-builder-surface-dim)',
                    'surface-bright': 'var(--layout-builder-surface-bright)',
                    'surface-container-lowest':
                        'var(--layout-builder-surface-container-lowest)',
                    'surface-container-low':
                        'var(--layout-builder-surface-container-low)',
                    'surface-container': 'var(--layout-builder-surface-container)',
                    'surface-container-high':
                        'var(--layout-builder-surface-container-high)',
                    'surface-container-highest':
                        'var(--layout-builder-surface-container-highest)',
                    'surface-variant': 'var(--layout-builder-surface-variant)',

                    // Text
                    'on-surface': 'var(--layout-builder-on-surface)',
                    'on-surface-variant': 'var(--layout-builder-on-surface-variant)',

                    // Semantic
                    error: 'var(--layout-builder-error)',
                    'error-container': 'var(--layout-builder-error-container)',
                    'on-error': 'var(--layout-builder-on-error)',
                    'on-error-container': 'var(--layout-builder-on-error-container)',

                    success: 'var(--layout-builder-success)',
                    'success-container': 'var(--layout-builder-success-container)',
                    'on-success': 'var(--layout-builder-on-success)',
                    'on-success-container':
                        'var(--layout-builder-on-success-container)',

                    warning: 'var(--layout-builder-warning)',
                    'warning-container': 'var(--layout-builder-warning-container)',
                    'on-warning': 'var(--layout-builder-on-warning)',
                    'on-warning-container':
                        'var(--layout-builder-on-warning-container)',

                    // Outline
                    outline: 'var(--layout-builder-outline)',
                    'outline-variant': 'var(--layout-builder-outline-variant)',
                },
            },

            spacing: {
                'layout-builder-xs': 'var(--layout-builder-spacing-xs)',
                'layout-builder-sm': 'var(--layout-builder-spacing-sm)',
                'layout-builder-md': 'var(--layout-builder-spacing-md)',
                'layout-builder-lg': 'var(--layout-builder-spacing-lg)',
                'layout-builder-xl': 'var(--layout-builder-spacing-xl)',
                'layout-builder-2xl': 'var(--layout-builder-spacing-2xl)',
                'layout-builder-3xl': 'var(--layout-builder-spacing-3xl)',
            },

            fontFamily: {
                'layout-builder-headline': 'var(--layout-builder-font-headline)',
                'layout-builder-body': 'var(--layout-builder-font-body)',
                'layout-builder-mono': 'var(--layout-builder-font-mono)',
            },

            fontSize: {
                'layout-builder-display-lg': 'var(--layout-builder-text-display-lg)',
                'layout-builder-display-md': 'var(--layout-builder-text-display-md)',
                'layout-builder-headline-lg': 'var(--layout-builder-text-headline-lg)',
                'layout-builder-headline-md': 'var(--layout-builder-text-headline-md)',
                'layout-builder-headline-sm': 'var(--layout-builder-text-headline-sm)',
                'layout-builder-title-lg': 'var(--layout-builder-text-title-lg)',
                'layout-builder-title-md': 'var(--layout-builder-text-title-md)',
                'layout-builder-title-sm': 'var(--layout-builder-text-title-sm)',
                'layout-builder-body-lg': 'var(--layout-builder-text-body-lg)',
                'layout-builder-body-md': 'var(--layout-builder-text-body-md)',
                'layout-builder-body-sm': 'var(--layout-builder-text-body-sm)',
                'layout-builder-label-lg': 'var(--layout-builder-text-label-lg)',
                'layout-builder-label-md': 'var(--layout-builder-text-label-md)',
                'layout-builder-label-sm': 'var(--layout-builder-text-label-sm)',
            },

            borderRadius: {
                'layout-builder-sm': 'var(--layout-builder-radius-sm)',
                'layout-builder-md': 'var(--layout-builder-radius-md)',
                'layout-builder-lg': 'var(--layout-builder-radius-lg)',
                'layout-builder-xl': 'var(--layout-builder-radius-xl)',
                'layout-builder-full': 'var(--layout-builder-radius-full)',
            },

            transitionDuration: {
                'layout-builder-fast': 'var(--layout-builder-transition-fast)',
                'layout-builder-base': 'var(--layout-builder-transition-base)',
                'layout-builder-slow': 'var(--layout-builder-transition-slow)',
            },

            backdropBlur: {
                'layout-builder-sm': 'var(--layout-builder-blur-sm)',
                'layout-builder-md': 'var(--layout-builder-blur-md)',
                'layout-builder-lg': 'var(--layout-builder-blur-lg)',
                'layout-builder-xl': 'var(--layout-builder-blur-xl)',
            },

            boxShadow: {
                'layout-builder-ambient': '0 12px 32px var(--layout-builder-shadow-ambient)',
                'layout-builder-lg': '0 10px 25px rgba(0, 0, 0, 0.3)',
            },

            backgroundImage: {
                'layout-builder-primary-gradient':
                    'linear-gradient(135deg, var(--layout-builder-primary-container) 0%, #5a00c6 100%)',
                'layout-builder-secondary-gradient':
                    'linear-gradient(135deg, var(--layout-builder-secondary-container) 0%, #3131c0 100%)',
                'layout-builder-tertiary-gradient':
                    'linear-gradient(135deg, var(--layout-builder-tertiary) 0%, var(--layout-builder-tertiary-container) 100%)',
            },
        },
    },

    plugins: [],
}
