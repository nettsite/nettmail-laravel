import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        lib: {
            entry: 'resources/js/unlayer-editor.jsx',
            name: 'NettMailUnlayer',
            formats: ['iife'],
            fileName: () => 'unlayer-editor.js',
        },
    },
});
