import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

// Vite khusus pratinjau komponen; tidak terhubung dengan vite.config.js milik Laravel di akar proyek.
export default defineConfig({
    root: import.meta.dirname,
    plugins: [react()],
    server: { port: 5199 },
    build: { outDir: 'dist', emptyOutDir: true },
});
