/** @type {import('tailwindcss').Config} */
export default ({
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    extend: {
     animation: {
        "fade": "fadeOut .5s ease-in-out",
      },

      // that is actual animation
      keyframes: (theme) => ({
        fadeOut: {
          "0%": {
            backgroundColor: theme("colors.transparent")
          },
          "100%": {
            backgroundColor: theme("colors.gray-300")
          },
        },
      }),},
    fontFamily: {
      'poppins': ['poppins', 'sans-serif']
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
});

