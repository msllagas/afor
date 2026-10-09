import '../css/app.css';
import '../css/boardlist.css';
import '../css/tiptap.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import RealtimeRoot from './components/realtime/RealtimeRoot.vue';
import { initializeTheme } from './composables/useAppearance';
import { configureRealtime } from './lib/realtime';
import { formatPageTitle } from './lib/utils';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

configureRealtime();

createInertiaApp({
    title: (title) => formatPageTitle(title, appName),
    resolve: (name) =>
        resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => [h(App, props), h(RealtimeRoot)] })
            .use(plugin)
            .mount(el);
    },
    // Page visits only: the board's background saves are async and don't show it.
    progress: {
        delay: 250,
        color: 'var(--primary)',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
