<script setup lang="ts">
import { img, isInternal, type EventData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { Calendar, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

export type PopupData = {
    /** Cambia en cada guardado: a quien cerró el anterior, el nuevo le vuelve a salir. */
    version: string;
    mode: 'once' | 'until_dismissed';
    kind: 'image' | 'event';
    text: string | null;
    image_path: string | null;
    event: EventData | null;
};

const props = defineProps<{ popup: PopupData }>();

/*
 * localStorage: "ya no lo muestres" — el modo 1 vez y el botón "No mostrar más".
 * sessionStorage: la X en el modo hasta-descartar, que lo oculta solo por esta visita.
 */
const foreverKey = computed(() => `popup-dismissed-v${props.popup.version}`);
const sessionKey = computed(() => `popup-closed-v${props.popup.version}`);

const open = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;

function read(storage: () => Storage, key: string): boolean {
    try {
        return storage().getItem(key) === '1';
    } catch {
        return false;
    }
}

function write(storage: () => Storage, key: string) {
    try {
        storage().setItem(key, '1');
    } catch {}
}

onMounted(() => {
    if (read(() => localStorage, foreverKey.value)) return;
    if (props.popup.mode === 'until_dismissed' && read(() => sessionStorage, sessionKey.value)) return;

    timer = setTimeout(() => {
        open.value = true;
        // "1 vez" quiere decir una: se anota apenas se ve, aunque no lo cierre.
        if (props.popup.mode === 'once') write(() => localStorage, foreverKey.value);
    }, 600);
});

function close() {
    if (props.popup.mode === 'until_dismissed') write(() => sessionStorage, sessionKey.value);
    open.value = false;
}

function dismissForever() {
    write(() => localStorage, foreverKey.value);
    open.value = false;
}

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape' && open.value) close();
}

// Con la ventana abierta el fondo no se desplaza: en el celular, el dedo movería la página de atrás.
watch(open, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => {
    clearTimeout(timer);
    window.removeEventListener('keydown', onKey);
    document.body.style.overflow = '';
});

const event = computed(() => (props.popup.kind === 'event' ? props.popup.event : null));
const image = computed(() => img(event.value ? event.value.image_path : props.popup.image_path));

/** Igual que EventCard: la imagen sigue a su propio enlace, y si no tiene, al del botón. */
const imageTarget = computed(() => (event.value ? event.value.image_url || event.value.cta_url : null));
const imageWrapper = computed(() => (!imageTarget.value ? 'div' : isInternal(imageTarget.value) ? Link : 'a'));
const imageAttrs = computed(() => {
    if (!imageTarget.value) return {};
    return isInternal(imageTarget.value) ? { href: imageTarget.value } : { href: imageTarget.value, target: '_blank', rel: 'noopener' };
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 px-4 py-6"
                role="dialog"
                aria-modal="true"
                :aria-label="event?.title ?? 'Aviso'"
                @click.self="close"
            >
                <div class="relative flex max-h-full w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <!-- La X queda fija arriba aunque el contenido se desplace, y se ve sobre cualquier imagen. -->
                    <button
                        type="button"
                        class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-brand-ink shadow-md ring-1 ring-black/5 transition hover:bg-white active:scale-95"
                        aria-label="Cerrar"
                        @click="close"
                    >
                        <X class="h-5 w-5" />
                    </button>

                    <div class="overflow-y-auto overscroll-contain">
                        <component :is="imageWrapper" v-if="image" v-bind="imageAttrs" class="block bg-brand-light" @click="imageTarget && close()">
                            <img :src="image" :alt="event?.title ?? ''" class="mx-auto max-h-[50dvh] w-full object-contain" />
                        </component>

                        <div v-if="popup.text || event" class="px-5 py-5" :class="{ 'pr-14': !image }">
                            <p v-if="popup.text" class="whitespace-pre-line text-[15px] leading-relaxed text-brand-body">{{ popup.text }}</p>

                            <template v-if="event">
                                <h2 class="font-heading text-xl font-semibold leading-snug text-brand-ink" :class="{ 'mt-4': popup.text }">
                                    {{ event.title }}
                                </h2>
                                <p v-if="event.date_label" class="mt-2 flex items-center gap-2 text-sm text-brand-muted">
                                    <Calendar class="h-4 w-4 shrink-0 text-brand-orange" />
                                    {{ event.date_label }}
                                </p>
                                <template v-if="event.cta_label && event.cta_url">
                                    <Link
                                        v-if="isInternal(event.cta_url)"
                                        :href="event.cta_url"
                                        class="mt-4 inline-block rounded-full bg-brand-sky px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-sky-dark"
                                        @click="close"
                                    >
                                        {{ event.cta_label }}
                                    </Link>
                                    <a
                                        v-else
                                        :href="event.cta_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="mt-4 inline-block rounded-full bg-brand-sky px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-sky-dark"
                                    >
                                        {{ event.cta_label }}
                                    </a>
                                </template>
                            </template>
                        </div>
                    </div>

                    <!-- Fuera del scroll: "No mostrar más" y "Cerrar" se ven siempre, aunque el contenido sea largo. -->
                    <div class="flex shrink-0 items-center justify-end gap-4 border-t border-brand-line/40 px-5 py-3">
                        <button
                            v-if="popup.mode === 'until_dismissed'"
                            type="button"
                            class="mr-auto text-sm text-brand-muted underline-offset-2 hover:underline"
                            @click="dismissForever"
                        >
                            No mostrar más
                        </button>
                        <button
                            type="button"
                            class="rounded-full bg-brand-ink px-5 py-2 text-sm font-medium text-white transition hover:opacity-90 active:scale-95"
                            @click="close"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
