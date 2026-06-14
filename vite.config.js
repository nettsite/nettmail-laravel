import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'resources/dist',
        emptyOutDir: true,
        cssCodeSplit: false,
        lib: {
            entry: 'resources/js/grapesjs-editor.js',
            name: 'NettMailGrapesJS',
            formats: ['iife'],
            fileName: () => 'grapesjs-editor.js',
        },
        rollupOptions: {
            output: {
                assetFileNames: 'grapesjs-editor.[ext]',
            },
        },
    },
});
