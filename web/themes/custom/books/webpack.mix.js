let mix = require('laravel-mix');

mix
  .disableSuccessNotifications()
  .postCss('./src/main.css', 'build', [
    require('tailwindcss'),
    require('autoprefixer'),
  ])
  .browserSync({
    proxy: 'books.local',
    files: [
      'build/main.css',
      'templates/**/*',
      'books.theme',
    ]
  })
