import { createApp, h } from 'vue';
import { createInertiaApp, Link } from '@inertiajs/vue3';
import Index from './Pages/Index.vue';
import Show from './Pages/Show.vue';

createInertiaApp({
    resolve: name => {
        const pages = {
            'Evolve/Index': Index,
            'Evolve/Show': Show,
        };
        return pages[name];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .component('Link', Link) // Register globally
            .mount(el);
    },
});