import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    build: {
        outDir: 'dist',
        rollupOptions: {
            input: 'resources/js/app.js',
            output: {
                entryFileNames: 'evolve.js',
                format: 'iife', // Self-contained script
            },
        },
    },
});