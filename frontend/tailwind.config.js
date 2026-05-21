/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./src/**/*.{js,jsx,ts,tsx}",
    ],
    theme: {
        extend: {
            colors: {
                primary: '#1a1a2e',
                secondary: '#e94560',
                accent: '#f5a623',
            },
        },
    },
    plugins: [],
}
