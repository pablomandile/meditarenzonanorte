<script setup lang="ts">
import { img, isInternal, lines, mapsUrl, paragraphs, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { ChevronDown, Clock, MapPin, RotateCcw, RotateCw, Ticket } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{ section: SectionData }>();

/** Una clase por línea; sin contenido no hay dorso ni giro. */
const cycle = computed(() => lines(props.section.content.cycle));
const flipped = ref(false);

/**
 * Cerrar al hacer click en cualquier parte del dorso, salvo que se esté
 * seleccionando texto: copiarse el nombre de una clase no debería taparla.
 */
function closeUnlessSelecting() {
    if (!window.getSelection()?.toString()) flipped.value = false;
}

const expanded = ref(false);
const body = ref<HTMLElement>();
const clipped = ref(false);

/**
 * En desktop el alto de la tarjeta lo fija el afiche y el texto se recorta a lo
 * que entra (ver el layout más abajo). Acá se detecta si quedó recortado, que es
 * lo único que no se puede saber desde CSS. En mobile el texto nunca se recorta,
 * así que scrollHeight == clientHeight y el botón no aparece.
 */
function measure() {
    const el = body.value;

    clipped.value = !!el && el.scrollHeight - el.clientHeight > 4;
}

let observer: ResizeObserver | null = null;

onMounted(() => {
    measure();
    observer = new ResizeObserver(() => measure());
    if (body.value) observer.observe(body.value);
});

onBeforeUnmount(() => observer?.disconnect());

// Al plegar de nuevo hay que volver a medir: el recorte reaparece.
watch(expanded, () => nextTick(measure));
</script>

<template>
    <section class="py-8 md:py-10">
        <div class="mx-auto max-w-6xl px-4">
            <div class="overflow-hidden rounded-xl bg-brand-cream md:relative">
                <div class="grid items-center gap-0 md:grid-cols-2">
                    <!--
                        La imagen manda su propio alto (h-auto, sin object-cover): los
                        afiches suelen ser verticales 4:5 y estirarlos al alto de la
                        columna de texto los recortaba.
                    -->
                    <div v-if="section.content.image" class="[perspective:1400px]">
                        <!--
                            El afiche y las clases del ciclo son las dos caras de la
                            misma tarjeta. El frente queda en el flujo (define el alto)
                            y el dorso va absoluto encima, girado 180°: al rotar el
                            contenedor, backface-visibility deja ver sólo una cara.
                        -->
                        <div
                            class="relative transition-transform duration-700 [transform-style:preserve-3d] motion-reduce:transition-none"
                            :class="flipped ? '[transform:rotateY(180deg)]' : ''"
                        >
                            <component
                                :is="cycle.length ? 'button' : 'div'"
                                v-bind="cycle.length ? { type: 'button', 'aria-expanded': flipped, 'aria-label': 'Ver las clases del ciclo' } : {}"
                                class="relative block w-full [backface-visibility:hidden]"
                                :class="cycle.length ? 'group/flip cursor-pointer' : ''"
                                @click="cycle.length && (flipped = true)"
                            >
                                <img
                                    :src="img(section.content.image)"
                                    :alt="section.content.heading ?? ''"
                                    class="h-auto w-full rounded-t-xl md:rounded-l-xl md:rounded-tr-none"
                                />

                                <span
                                    v-if="cycle.length"
                                    class="absolute bottom-3 right-3 flex items-center gap-1.5 rounded-full bg-brand-sky/95 px-3 py-1.5 text-xs font-medium uppercase tracking-wide text-white shadow-md transition group-hover/flip:bg-brand-sky-dark"
                                >
                                    <RotateCw class="h-3.5 w-3.5" /> clases del ciclo
                                </span>
                            </component>

                            <!--
                                El dorso entero vuelve al afiche, igual que el afiche
                                entero lo da vuelta. El botón "volver" sigue estando
                                para el teclado y para que se vea que se puede cerrar.
                            -->
                            <div
                                v-if="cycle.length"
                                class="absolute inset-0 flex cursor-pointer flex-col overflow-y-auto rounded-t-xl bg-gradient-to-br from-brand-sky to-brand-sky-dark p-6 text-white [backface-visibility:hidden] [transform:rotateY(180deg)] md:rounded-l-xl md:rounded-tr-none md:p-8"
                                :aria-hidden="!flipped"
                                @click="closeUnlessSelecting"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-display text-2xl uppercase tracking-wide md:text-3xl">Clases del ciclo</h3>
                                    <button
                                        type="button"
                                        class="flex shrink-0 items-center gap-1 rounded-full bg-white/20 px-3 py-1.5 text-xs font-medium uppercase tracking-wide transition hover:bg-white/30"
                                        :tabindex="flipped ? 0 : -1"
                                        @click="flipped = false"
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" /> volver
                                    </button>
                                </div>

                                <ol class="mt-5 space-y-3">
                                    <li v-for="(item, i) in cycle" :key="i" class="flex items-start gap-3">
                                        <span
                                            class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-cream text-xs font-bold text-brand-sky-dark"
                                        >
                                            {{ i + 1 }}
                                        </span>
                                        <span class="text-[15px] font-medium leading-snug md:text-base">{{ item }}</span>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!--
                        Plegado y en desktop, la columna de texto sale del flujo
                        (md:absolute): así el alto de la tarjeta lo define solo el
                        afiche y coincide con él, techo y fondo. El texto se recorta
                        a ese alto y el botón lo despliega, y ahí la columna vuelve
                        al flujo y la tarjeta crece.
                    -->
                    <div
                        class="flex flex-col justify-center p-8 md:p-10"
                        :class="[
                            !section.content.image ? 'md:col-span-2' : '',
                            section.content.image && !expanded ? 'md:absolute md:inset-y-0 md:right-0 md:w-1/2 md:overflow-hidden' : '',
                        ]"
                    >
                        <h2 class="shrink-0 whitespace-pre-line font-heading text-3xl font-light leading-snug text-brand-sky md:text-[32px]">
                            {{ section.content.heading }}
                        </h2>

                        <div v-if="paragraphs(section.content.body).length" ref="body" class="relative min-h-0 overflow-hidden">
                            <p v-for="(p, i) in paragraphs(section.content.body)" :key="i" class="mt-4 leading-relaxed text-brand-body">
                                {{ p }}
                            </p>

                            <!-- Degradado para que el corte no quede a mitad de renglón. -->
                            <div
                                v-if="clipped && !expanded"
                                class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-brand-cream to-transparent"
                                aria-hidden="true"
                            ></div>
                        </div>

                        <button
                            v-if="clipped || expanded"
                            type="button"
                            class="mt-3 flex shrink-0 items-center gap-1 self-start text-sm font-medium text-brand-sky hover:underline"
                            :aria-expanded="expanded"
                            @click="expanded = !expanded"
                        >
                            {{ expanded ? 'menos' : 'más…' }}
                            <ChevronDown class="h-4 w-4 transition-transform" :class="{ 'rotate-180': expanded }" />
                        </button>

                        <ul class="mt-6 shrink-0 space-y-3 text-brand-body">
                            <li v-if="section.content.schedule" class="flex items-start gap-3">
                                <Clock class="mt-0.5 h-5 w-5 shrink-0 text-brand-orange" />
                                <span class="whitespace-pre-line font-medium">{{ section.content.schedule }}</span>
                            </li>
                            <li v-if="section.content.location" class="flex items-start gap-3">
                                <MapPin class="mt-0.5 h-5 w-5 shrink-0 text-brand-orange" />
                                <a
                                    :href="mapsUrl(section.content.location)"
                                    target="_blank"
                                    rel="noopener"
                                    title="Ver en Google Maps"
                                    class="underline decoration-brand-muted/60 underline-offset-2 transition hover:text-brand-sky hover:decoration-brand-sky"
                                >
                                    {{ section.content.location }}
                                </a>
                            </li>
                            <li v-if="section.content.price" class="flex items-start gap-3">
                                <Ticket class="mt-0.5 h-5 w-5 shrink-0 text-brand-orange" />
                                <span class="font-semibold">{{ section.content.price }}</span>
                            </li>
                        </ul>

                        <div v-if="section.content.cta_label && section.content.cta_url" class="mt-7 shrink-0">
                            <Link
                                v-if="isInternal(section.content.cta_url)"
                                :href="section.content.cta_url"
                                class="inline-block rounded-full bg-brand-orange px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                            >
                                {{ section.content.cta_label }}
                            </Link>
                            <a
                                v-else
                                :href="section.content.cta_url"
                                target="_blank"
                                rel="noopener"
                                class="inline-block rounded-full bg-brand-orange px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                            >
                                {{ section.content.cta_label }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
