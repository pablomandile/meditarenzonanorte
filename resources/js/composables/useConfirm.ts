import { ref } from 'vue';

export type ConfirmOptions = {
    title: string;
    /** Obligatoria: radix la usa como aria-describedby del diálogo. */
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    /** Pinta el botón de confirmar en rojo. Para acciones que borran datos. */
    destructive?: boolean;
};

/**
 * Estado a nivel de módulo: cualquier página pide la confirmación y el único
 * <ConfirmDialog /> montado en AdminLayout la muestra.
 */
const open = ref(false);

/**
 * No se limpia al cerrar: el contenido tiene que seguir montado mientras corre
 * la animación de salida, y así el diálogo no desaparece de golpe.
 */
const options = ref<ConfirmOptions | null>(null);

let resolver: ((accepted: boolean) => void) | null = null;

export function useConfirm() {
    /** Reemplazo de window.confirm(): resuelve true si el usuario acepta. */
    function confirm(newOptions: ConfirmOptions): Promise<boolean> {
        // Si quedara uno abierto, se descarta para no dejar su promesa colgada.
        resolver?.(false);

        options.value = newOptions;
        open.value = true;

        return new Promise((resolve) => {
            resolver = resolve;
        });
    }

    /**
     * Se limpia el resolver antes de cerrar: cerrar dispara `update:open`, que
     * vuelve a llamar acá con false, y esa segunda llamada no debe pisar la
     * decisión real del usuario.
     */
    function settle(accepted: boolean) {
        const resolve = resolver;
        resolver = null;
        open.value = false;
        resolve?.(accepted);
    }

    return { open, options, confirm, settle };
}
