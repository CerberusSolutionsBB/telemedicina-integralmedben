import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite'


export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        Components({
            dirs: [
                'resources/js/Components',
                'resources/js/Layouts'
            ],
            extensions: ['vue'],
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('@vueup/vue-quill') || id.includes('quill')) return 'vendor-quill';
                    if (id.includes('@tiptap') || id.includes('prosemirror')) return 'vendor-tiptap';
                    if (/node_modules\/(vue|@vue|@inertiajs)\//.test(id)) return 'vendor-vue';
                },
            },
        },
    },
});
