/** @type {import('tailwindcss').Config} */
// Palet mengikuti "Tema Pasundan" (Asset Care GPCH):
// navy  -> hijau GP (#006632), gold -> emas (#B8862B), slate -> tinta/kertas hangat, marun -> #3A1410.
export default {
  content: ['./resources/views/**/*.blade.php', './app/**/*.php'],
  safelist: [{ pattern: /^(border-l|border-t|text|bg)-(emerald|rose|amber|indigo|orange|slate|teal|sky|navy|gold|zinc|stone|yellow|marun)-(50|100|500|600|700|800|900)$/ }],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', '"Segoe UI"', 'sans-serif'],
        serif: ['"Cormorant Garamond"', '"Playfair Display"', 'Georgia', 'serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
      },
      colors: {
        navy:  { 50:'#EEF6F0', 100:'#D5EADB', 200:'#A9D3B6', 300:'#7CBC92', 400:'#4FA26F', 500:'#2F8A57', 600:'#1A7644', 700:'#006632', 800:'#004F27', 900:'#003A1D' },
        gold:  { 50:'#FBF6EA', 100:'#F4E7C6', 200:'#ECD9A4', 300:'#E2C886', 400:'#D9B76A', 500:'#B8862B', 600:'#96691C', 700:'#7A5416', 800:'#5E4011', 900:'#432E0C' },
        slate: { 50:'#F8F3E8', 100:'#F1EADB', 200:'#E7DDCB', 300:'#D6C8B0', 400:'#A39484', 500:'#6E5F52', 600:'#5A4B3F', 700:'#45352B', 800:'#2A1A14', 900:'#1F130E' },
        marun: { 50:'#F7EEEC', 100:'#EBD5D1', 500:'#7A3A30', 600:'#5C2620', 700:'#4A1C16', 800:'#3A1410', 900:'#2A0E0B' },
        sky:   { 50:'#EEF2F6', 100:'#DCE4EC', 500:'#4A6886', 600:'#34506E', 700:'#2B4259', 800:'#223547', 900:'#1A2836' },
        paper: '#FAF6EC',
        kertas2: '#F1EADB',
        bar: '#FFFDF8',
      },
      borderRadius: { kartu: '.75rem', kontrol: '.45rem' },
    }
  }
};
