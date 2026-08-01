import { ref } from 'vue';

export type MediaImage = {
    path: string;
    url: string;
    name: string;
    /** true si viene del contenido sembrado del sitio (seed/…). */
    seeded: boolean;
    size: number;
};

/**
 * Estado a nivel de módulo: una página de edición monta un ImageField por campo
 * (y varios más dentro de tarjetas o galerías), y todos comparten la misma lista
 * en lugar de pedirla cada uno.
 */
const images = ref<MediaImage[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
let loaded = false;

export function useMediaLibrary() {
    async function load(force = false) {
        if (loading.value || (loaded && !force)) return;

        loading.value = true;
        error.value = null;

        try {
            const response = await fetch(route('admin.media.index'), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            images.value = (await response.json()).images;
            loaded = true;
        } catch {
            error.value = 'No se pudo cargar la galería. Probá de nuevo.';
        } finally {
            loading.value = false;
        }
    }

    /** Al subir una imagen nueva la lista queda vieja: se recarga al abrir. */
    function invalidate() {
        loaded = false;
    }

    return { images, loading, error, load, invalidate };
}
