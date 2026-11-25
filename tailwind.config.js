import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                ink: {
                    DEFAULT: '#071512',
                    soft: '#14302a',
                    muted: '#3d5a52',
                },
                paper: {
                    DEFAULT: '#eef2ef',
                    warm: '#f6f4ef',
                    deep: '#e2e8e4',
                },
                accent: {
                    DEFAULT: '#0d9488',
                    deep: '#0f766e',
                    bright: '#2dd4bf',
                },
            },
            fontFamily: {
                sans: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
                display: ['Newsreader', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            fontSize: {
                'display': ['clamp(2.75rem, 6vw, 5.5rem)', { lineHeight: '1.02', letterSpacing: '-0.03em' }],
                'display-sm': ['clamp(2rem, 4vw, 3.25rem)', { lineHeight: '1.08', letterSpacing: '-0.025em' }],
                'display-feature': ['clamp(2rem, 3.6vw, 3rem)', { lineHeight: '1.12', letterSpacing: '-0.025em' }],
            },
            maxWidth: {
                site: '72rem',
            },
            transitionTimingFunction: {
                editorial: 'cubic-bezier(0.22, 1, 0.36, 1)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(1.25rem)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'ken-burns': {
                    '0%': { transform: 'scale(1)' },
                    '100%': { transform: 'scale(1.06)' },
                },
                'line-grow': {
                    '0%': { transform: 'scaleX(0)' },
                    '100%': { transform: 'scaleX(1)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.9s cubic-bezier(0.22, 1, 0.36, 1) both',
                'ken-burns': 'ken-burns 12s ease-out forwards',
                'line-grow': 'line-grow 0.8s cubic-bezier(0.22, 1, 0.36, 1) both',
            },
        },
    },

    plugins: [forms, typography],
};
