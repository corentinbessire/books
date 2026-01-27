import { defineConfig } from 'vite';
import { resolve } from 'path';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  build: {
    outDir: 'build',
    sourcemap: true,
    manifest: true,
    lib: {
      entry: resolve(__dirname, './js/main.js'),
      name: 'drupalTheme',
      formats: ['es'],
      fileName: () => 'main.js',
    },
    rollupOptions: {
      output: {
        assetFileNames: (assetInfo) => {
          if (assetInfo.names?.some((name) => name.endsWith('.css'))) {
            return 'styles.css';
          }
          return '[name].[ext]';
        },
      },
    },
  },
  define: {
    Alpine: 'Alpine',
  },
  plugins: [tailwindcss()],
  css: {
    devSourcemap: true,
    extract: 'styles.css',
  },
  resolve: {
    alias: {
      alpinejs: 'alpinejs',
    },
  },
});
