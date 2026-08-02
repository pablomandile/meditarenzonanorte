import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

/** Cuánto tiempo se sigue corrigiendo el destino después de apuntarle. */
const SEGUIMIENTO_MS = 2500;

/**
 * Lleva la vista al ancla de la URL (/cursos-y-retiros#retiro-de-agosto).
 *
 * Hace falta porque el sitio es una SPA y el navegador no puede hacerlo solo: cuando
 * busca el ancla al abrir el enlace, Vue todavía no dibujó la sección, y al llegar
 * por un enlace de Inertia no hay navegación de documento que lo dispare.
 *
 * Y no alcanza con apuntar una vez: los afiches no declaran alto, así que al cargarse
 * empujan el contenido y el destino se corre. Medido: una ficha que estaba más abajo
 * quedaba 700 px desviada. Por eso se vuelve a apuntar mientras la página siga
 * creciendo, y se deja de insistir en cuanto la persona toca el scroll.
 */
export function useHashScroll() {
    let observer: ResizeObserver | null = null;
    let timer: number | undefined;
    let stopNavigate: (() => void) | null = null;

    function target(): HTMLElement | null {
        const id = decodeURIComponent(window.location.hash.slice(1));

        // getElementById y no querySelector: un id que empieza con número (#2) es
        // válido en HTML pero rompe los selectores CSS.
        return id ? document.getElementById(id) : null;
    }

    function scrollToHash() {
        target()?.scrollIntoView();
    }

    function dejarDeSeguir() {
        observer?.disconnect();
        observer = null;
        window.clearTimeout(timer);
    }

    /** Apunta al ancla y corrige mientras el alto de la página siga cambiando. */
    function irAlAncla() {
        if (!target()) return;

        dejarDeSeguir();
        requestAnimationFrame(scrollToHash);

        observer = new ResizeObserver(scrollToHash);
        observer.observe(document.body);
        timer = window.setTimeout(dejarDeSeguir, SEGUIMIENTO_MS);
    }

    onMounted(() => {
        irAlAncla();

        // Si la persona se mueve por su cuenta, no se le pelea el scroll.
        (['wheel', 'touchstart', 'keydown'] as const).forEach((evento) =>
            window.addEventListener(evento, dejarDeSeguir, { once: true, passive: true }),
        );

        stopNavigate = router.on('navigate', irAlAncla);
    });

    onBeforeUnmount(() => {
        dejarDeSeguir();
        (['wheel', 'touchstart', 'keydown'] as const).forEach((evento) => window.removeEventListener(evento, dejarDeSeguir));
        stopNavigate?.();
    });
}
