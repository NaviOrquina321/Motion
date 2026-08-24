/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        supra: {
          red: '#FF0033',
          yellow: '#FFCC00',
          dark: '#0A0B0E',
          card: '#12151C',
          neon: '#00F0FF',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['Fira Code', 'monospace'],
        display: ['Orbitron', 'sans-serif'],
      },
      animation: {
        'pulse-fast': 'pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'glow': 'glow 2s ease-in-out infinite alternate',
      },
      keyframes: {
        glow: {
          '0%': { filter: 'drop-shadow(0 0 10px rgba(255, 0, 51, 0.6))' },
          '100%': { filter: 'drop-shadow(0 0 25px rgba(255, 0, 51, 0.9))' },
        }
      }
    },
  },
  plugins: [],
}
