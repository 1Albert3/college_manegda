/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,ts}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#003366', // Bleu Roi / Dark Blue
          light: '#004080',
          dark: '#00264d',
        },
        secondary: {
          DEFAULT: '#C5A059', // Or / Gold
          light: '#d4b77d',
          dark: '#b38f47',
        },
        neutral: {
          light: '#F9FAFB', // Crème / Off-white
          DEFAULT: '#F3F4F6',
          dark: '#1F2937',
        },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
        serif: ['Playfair Display', 'serif'], // For "Premium" feel
      },
    },
  },
  plugins: [],
}
