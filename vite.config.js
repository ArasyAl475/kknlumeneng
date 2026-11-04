import { readdirSync } from 'node:fs';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Setiap berkas resources/js/<modul>/*.js otomatis menjadi titik masuk, supaya rekan
// bisa menambah skrip modul tanpa mengedit berkas bersama ini.
const skripModul = readdirSync('resources/js', { withFileTypes: true })
    .filter((isi) => isi.isDirectory())
    .flatMap((folder) =>
        readdirSync(`resources/js/${folder.name}`)
            .filter((nama) => nama.endsWith('.js'))
            .map((nama) => `resources/js/${folder.name}/${nama}`),
    );

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', ...skripModul],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
