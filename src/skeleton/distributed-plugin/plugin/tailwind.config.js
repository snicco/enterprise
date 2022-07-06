module.exports = {
    content: [
        "./resources/**/*.php",
        "./resources/**/*.js",
    ],
    theme: {
        extend: {},
    },
    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography'), require('@tailwindcss/line-clamp'),],
}