/** @type {import('tailwindcss').Config} */
export default {
  content: ['./resources/views/**/*.blade.php', './app/**/*.php'],
  safelist: [{ pattern: /^(border-l|border-t|text|bg)-(emerald|rose|amber|indigo|orange|slate|teal|sky|navy|gold|zinc|stone|yellow)-(50|100|500|600|700|800|900)$/ }],
  theme: {
    extend: {
      fontFamily: { sans: ['"IBM Plex Sans"', 'system-ui', 'sans-serif'], mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'] },
      colors: {
        navy: { 50:'#eef3f9', 100:'#d6e2f0', 200:'#adc4e0', 500:'#2d5a8c', 600:'#1f4775', 700:'#12355b', 800:'#0d2744', 900:'#081a2e' },
        gold: { 50:'#fdf8ec', 100:'#f8ecc9', 400:'#dcae4a', 500:'#c8962e', 600:'#a67a1f' },
        paper: '#f6f7f9',
      },
    }
  }
};
