module.exports = {
  content: ["../src/**/*.phtml", "../templates/**/*.phtml"],
  theme: {
    extend: {
      listStyleType: {
        none: 'none',
        disc: 'disc',
        decimal: 'decimal',
        dash: '"— "',
      }
    },
  },
  plugins: [],
}
