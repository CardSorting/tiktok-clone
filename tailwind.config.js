/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.{js,jsx,ts,tsx,vue}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        'tiktok-pink': '#FF0050',
        'tiktok-black': '#010101',
        'tiktok-gray': '#F1F1F1',
      },
      aspectRatio: {
        '9/16': '9 / 16',
      },
      animation: {
        'bounce-x': 'bounce-x 1s infinite',
      },
      keyframes: {
        'bounce-x': {
          '0%, 100%': { transform: 'translateX(-25%)' },
          '50%': { transform: 'translateX(0)' },
        },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('@tailwindcss/aspect-ratio'),
  ],
}
