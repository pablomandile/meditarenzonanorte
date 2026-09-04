<script setup lang="ts">
import { type SectionData } from '@/lib/site';
import { ChevronLeft, ChevronRight, Star } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

type Review = { quote?: string | null; author?: string | null; rating?: number | null };

const props = defineProps<{ section: SectionData }>();

/** Una reseña sin texto no se muestra, aunque haya quedado guardada. */
const reviews = computed<Review[]>(() => ((props.section.content.reviews ?? []) as Review[]).filter((review) => (review.quote ?? '').trim() !== ''));

/**
 * Dos citas lado a lado no entran en un celular, así que abajo de md pasa a
 * una por pantalla. El breakpoint es el mismo `md` de Tailwind (768px).
 */
const perPage = ref(1);
let media: MediaQueryList | null = null;

function syncPerPage(event: MediaQueryList | MediaQueryListEvent) {
    perPage.value = event.matches ? 2 : 1;
}

const pages = computed<Review[][]>(() => {
    const chunks: Review[][] = [];

    for (let i = 0; i < reviews.value.length; i += perPage.value) {
        chunks.push(reviews.value.slice(i, i + perPage.value));
    }

    return chunks;
});

const index = ref(0);

/**
 * Dos señales distintas, que es fácil confundir:
 *  - `multi` (hay más de una reseña) decide el ancho y si van dos por fila. Con
 *    una sola, la banda se ve exactamente como antes de que esto fuera carrusel.
 *  - `many` (hay más de una página) decide las flechas, los puntitos y la
 *    rotación. Con dos reseñas en escritorio entran las dos juntas y no hay nada
 *    que pasar, pero igual van lado a lado.
 */
const multi = computed(() => reviews.value.length > 1);
const many = computed(() => pages.value.length > 1);

// Al pasar de escritorio a celular hay más páginas que antes (o menos): sin esto
// el índice podría quedar apuntando a una página que ya no existe.
watch(pages, () => {
    if (index.value > pages.value.length - 1) index.value = Math.max(0, pages.value.length - 1);
});

function go(to: number) {
    if (!pages.value.length) return;
    index.value = (to + pages.value.length) % pages.value.length;
}

// Rotación sola. Se frena mientras el visitante está mirando o navegando con el
// teclado, y no arranca si pidió que no haya movimiento.
const paused = ref(false);
let timer: ReturnType<typeof setInterval> | null = null;

function stop() {
    if (timer) clearInterval(timer);
    timer = null;
}

function start() {
    stop();

    if (!many.value || paused.value) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    timer = setInterval(() => go(index.value + 1), 7000);
}

watch([many, paused], start);

onMounted(() => {
    media = window.matchMedia('(min-width: 768px)');
    syncPerPage(media);
    media.addEventListener('change', syncPerPage);
    start();
});

onBeforeUnmount(() => {
    media?.removeEventListener('change', syncPerPage);
    stop();
});
</script>

<template>
    <section v-if="reviews.length" class="section-band bg-brand-light/50 py-14">
        <div
            class="mx-auto px-4"
            :class="multi ? 'max-w-5xl' : 'max-w-3xl'"
            @mouseenter="paused = true"
            @mouseleave="paused = false"
            @focusin="paused = true"
            @focusout="paused = false"
        >
            <h2 v-if="section.content.heading" class="mb-8 text-center font-heading text-2xl text-brand-ink md:text-3xl">
                {{ section.content.heading }}
            </h2>

            <div class="overflow-hidden">
                <div class="flex transition-transform duration-500 ease-out" :style="{ transform: `translateX(-${index * 100}%)` }">
                    <div
                        v-for="(page, p) in pages"
                        :key="p"
                        class="grid w-full shrink-0 gap-8"
                        :class="multi && 'md:grid-cols-2'"
                        :aria-hidden="p !== index"
                    >
                        <!-- La última reseña impar queda sola en su página: se centra
                             en vez de colgar de la mitad izquierda. -->
                        <figure
                            v-for="(review, r) in page"
                            :key="r"
                            class="text-center"
                            :class="multi && page.length === 1 && 'md:col-span-2 md:mx-auto md:max-w-2xl'"
                        >
                            <div class="mb-4 flex justify-center gap-1 text-brand-orange">
                                <Star
                                    v-for="i in 5"
                                    :key="i"
                                    class="h-5 w-5"
                                    :class="i <= (review.rating ?? 5) ? 'fill-current' : 'text-brand-line'"
                                />
                            </div>
                            <blockquote
                                class="font-light italic leading-relaxed text-brand-body"
                                :class="multi ? 'text-lg md:text-xl' : 'text-xl md:text-2xl'"
                            >
                                “{{ review.quote }}”
                            </blockquote>
                            <figcaption v-if="review.author" class="mt-5 font-medium text-brand-sky">— {{ review.author }}</figcaption>
                        </figure>
                    </div>
                </div>
            </div>

            <div v-if="many" class="mt-8 flex items-center justify-center gap-4">
                <button
                    type="button"
                    class="rounded-full p-2 text-brand-sky transition hover:bg-white/70"
                    aria-label="Reseñas anteriores"
                    @click="go(index - 1)"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>

                <div class="flex gap-2">
                    <button
                        v-for="(page, p) in pages"
                        :key="p"
                        type="button"
                        class="h-2.5 w-2.5 rounded-full transition"
                        :class="p === index ? 'bg-brand-sky' : 'bg-brand-line hover:bg-brand-pale'"
                        :aria-label="`Ir al grupo ${p + 1} de ${pages.length}`"
                        :aria-current="p === index"
                        @click="go(p)"
                    />
                </div>

                <button
                    type="button"
                    class="rounded-full p-2 text-brand-sky transition hover:bg-white/70"
                    aria-label="Reseñas siguientes"
                    @click="go(index + 1)"
                >
                    <ChevronRight class="h-5 w-5" />
                </button>
            </div>
        </div>
    </section>
</template>
