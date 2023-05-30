/** @type {import('tailwindcss').Config} */
const withMT = require("@material-tailwind/html/utils/withMT");
module.exports = ({
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    extend: {},
    fontFamily: {
      'poppins': ['poppins', 'sans-serif']
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
});

