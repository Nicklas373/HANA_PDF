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
      }),
    },
    colors: {
        transparent: 'transparent',
        current: 'currentColor',
        'white': '#ffffff',
        'black': '#000000',
        'lt': '#EFF3F4',
        'pc': '#3A638D',
        'sc': '#88BBE5',
        'ac': '#4DAAAA',
        'dt': '#27343F',
        'dt1': '#34404A',
        'dt2': '#404C56',
        'dt3': '#4D5861',
        'dt4': '#5A646C',
        'dt5': '#677077',
        'lt1': '#E0E4E5',
        'lt2': '#D1D5D6',
        'lt3': '#C2C5C6',
        'lt4': '#B3B6B7',
        'lt5': '#A4A7A8',
        'pc1': '#41566B',
        'pc2': '#7AB1DD',
        'pc3': '#C9E4FF',
        'pc4': '#E3F1FF',
        'rt1': '#A84E4E',
        'rt2': '#992E2E',
        'rt3': '#D69696',
        'rt4': '#FFE6E6',
        'rt5': '#331717',
        'ot1': '#A87B4E',
        'ot2': '#99632E',
        'ot3': '#D6B696',
        'ot4': '#FFF2E6',
        'ot5': '#332517',
        'gt1': '#4EA579',
        'gt2': '#2E9963',
        'gt3': '#96D6B6',
        'gt4': '#E6FFF2',
        'gt5': '#173325',
        'sc': '#88BBE5'
    },
    fontFamily: {
      'poppins': ['poppins', 'sans-serif'],
      'magistral': ['magistral', 'sans-serif'],
      'magistral-condensed' : ['magistral-condensed', 'sans-serif'],
      'magistral-compressed' : ['magistral-compressed', 'sans-serif'],
      'quicksand': ['quicksand', 'sans-serif']
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
});

