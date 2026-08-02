import '../css/app.css';

import '@fontsource/anton';
import '@fontsource/roboto/300.css';
import '@fontsource/roboto/400.css';
import '@fontsource/roboto/500.css';
import '@fontsource/roboto/700.css';
import '@fontsource/roboto-slab/400.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initializeTheme } from './composables/useAppearance';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

/**
 * El nombre del sitio sale del prop compartido `name`, que a su vez viene del ajuste
 * "Nombre del sitio" del panel. VITE_APP_NAME queda sólo como red de seguridad: se
 * hornea en el bundle al compilar, así que quien compile decide el nombre y no el
 * dueño del sitio — que es cómo el título terminó mostrando el nombre viejo.
 */
let siteName = import.meta.env.VITE_APP_NAME || '';

createInertiaApp({
    title: (title) => [title, siteName].filter(Boolean).join(' - '),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Antes de montar, así ningún <Head> se renderiza con el nombre viejo.
        siteName = (props.initialPage.props as { name?: string }).name || siteName;

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#259ACF',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
