/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                // camelCase (matches your existing Blade files)
                bgLight: '#FCF6F5',
                cardLight: '#FCF6F5',
                borderDark: '#585D61',
                textMuted: '#585D61',

                // Flutter Mapped Colors
                primary: {
                    DEFAULT: '#63D1F6', // Light Blue
                    dark: '#585D61',    // Dark Grey
                },
                accent: '#DEAF4B',     // Yellow
                bgDark: '#FCF6F5',     // Off White
                cardDark: '#FCF6F5',   // Off White
            },
        },
    },
    plugins: [],
}