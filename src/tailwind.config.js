import desain from './design/tailwind.preset.js';

export default {
    presets: [desain],
    // Hanya berkas sumber komponen; jangan memindai node_modules atau dist.
    content: ['./index.html', './*.{js,jsx}', './{components,pages,data,lib}/**/*.{js,jsx}'],
};
