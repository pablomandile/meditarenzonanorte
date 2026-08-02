import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/**
 * Lleva la vista al ancla de la URL (/cursos-y-retiros#retiro-de-agosto).
 *
 * Hace falta porque el sitio es una SPA y el navegador no puede hacerlo solo: cuando
 * busca el ancla al abrir el enlace, Vue todavía no dibujó la sección, así que se
 * queda arriba. Y al llegar por un enlace de Inertia no hay navegación de documento
 * que lo dispare. Medido: sin esto sólo funcionaba al cambiar el hash estando ya en
 * la página.
 */
export function useHashScroll() {
    function scrollToHash() {
        const id = decodeURIComponent(window.location.hash.slice(1));

        if (!id) return;

        // getElementById y no querySelector: un id que empieza con número (#4) es
        // válido en HTML pero rompe los selectores CSS.
        requestAnimationFrame(() => document.getElementById(id)?.scrollIntoView());
    }

    let stopNavigate: (() => void) | null = null;

    onMounted(() => {
        scrollToHash();

        // Las portadas no declaran alto, así que al terminar de cargar empujan el
        // contenido hacia abajo y el destino se corre: se vuelve a apuntar.
        if (document.readyState !== 'complete') {
            window.addEventListener('load', scrollToHash, { once: true });
        }

        stopNavigate = router.on('navigate', scrollToHash);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('load', scrollToHash);
        stopNavigate?.();
    });
}
