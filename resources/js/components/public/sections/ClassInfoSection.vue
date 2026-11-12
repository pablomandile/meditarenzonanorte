<script setup lang="ts">
import { img, isInternal, joinNames, lines, mapsUrl, paragraphs, type SectionData } from '@/lib/site';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, Clock, MapPin, RotateCcw, RotateCw, Ticket } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{ section: SectionData }>();

/** Una clase por línea; sin contenido no hay dorso ni giro. */
const cycle = computed(() => lines(props.section.content.cycle));

/** "Ana, Luis" → "con Ana y Luis". Vacío no muestra nada. */
const teachers = computed(() => joinNames(props.section.content.teachers));
const flipped = ref(false);

/**
 * Cerrar al hacer click en cualquier parte del dorso, salvo que se esté
 * seleccionando texto: copiarse el nombre de una clase no debería taparla.
 */
function closeUnlessSelecting() {
    if (!window.getSelection()?.toString()) flipped.value = false;
}

/**
 * Qué hacer con una descripción que no entra junto al afiche: 'more' la corta y
 * "más…" despliega la ficha; 'scroll' la desplaza dentro del mismo alto y la
 * ficha no cambia de tamaño; 'flip' la pasa al dorso del afiche, que se da
 * vuelta como el de las clases del ciclo. Manda Ajustes del sitio: si ahí hay un
 * modo elegido vale para todas las fichas; si no (cada ficha elige), vale el de
 * la ficha, y si tampoco hay, "más…".
 */
const page = usePage<{ settings?: Record<string, string | null> }>();
const mode = computed(() => page.props.settings?.class_description_mode || props.section.content.body_overflow || 'more');
const scrolls = computed(() => mode.value === 'scroll');

const bodyParagraphs = computed(() => paragraphs(props.section.content.body));

/** Sin afiche no hay dorso: una ficha sin imagen se comporta como "más…". */
const flipsBody = computed(() => mode.value === 'flip' && !!props.section.content.image && bodyParagraphs.value.length > 0);

/** El dorso existe si hay clases del ciclo o si la descripción se pasó ahí. */
const hasBack = computed(() => cycle.value.length > 0 || flipsBody.value);

const backLabel = computed(() => {
    if (flipsBody.value) return cycle.value.length ? 'descripción y ciclo' : 'descripción';

    return 'clases del ciclo';
});

const expanded = ref(false);
const body = ref<HTMLElement>();
const clipped = ref(false);
/** Con scroll: si ya se llegó al final, el degradado sobra. */
const atEnd = ref(false);

/**
 * En desktop el alto de la tarjeta lo fija el afiche y el texto se recorta a lo
 * que entra (ver el layout más abajo). Acá se detecta si quedó recortado, que es
 * lo único que no se puede saber desde CSS. En mobile el texto nunca se recorta,
 * así que scrollHeight == clientHeight y el botón no aparece.
 */
function measure() {
    const el = body.value;

    clipped.value = !!el && el.scrollHeight - el.clientHeight > 4;
    atEnd.value = !!el && el.scrollHeight - el.clientHeight - el.scrollTop <= 4;
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
    <!--
        El id habilita /la-pagina#el-ancla. scroll-mt-24 (96px) compensa el header
        sticky de 80px: sin eso el navegador deja la ficha justo debajo y el
        encabezado la tapa.
    -->
    <section :id="section.content.anchor || undefined" class="scroll-mt-24">
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
                                :is="hasBack ? 'button' : 'div'"
                                v-bind="hasBack ? { type: 'button', 'aria-expanded': flipped, 'aria-label': `Ver ${backLabel}` } : {}"
                                class="relative block w-full [backface-visibility:hidden]"
                                :class="hasBack ? 'group/flip cursor-pointer' : ''"
                                @click="hasBack && (flipped = true)"
                            >
                                <img
                                    :src="img(section.content.image)"
                                    :alt="section.content.heading ?? ''"
                                    class="h-auto w-full rounded-t-xl md:rounded-l-xl md:rounded-tr-none"
                                />

                                <span
                                    v-if="hasBack"
                                    class="absolute bottom-3 right-3 flex items-center gap-1.5 rounded-full bg-brand-sky/95 px-3 py-1.5 text-xs font-medium uppercase tracking-wide text-white shadow-md transition group-hover/flip:bg-brand-sky-dark"
                                >
                                    <RotateCw class="h-3.5 w-3.5" /> {{ backLabel }}
                                </span>
                            </component>

                            <!--
                                El dorso entero vuelve al afiche, igual que el afiche
                                entero lo da vuelta. El botón "volver" sigue estando
                                para el teclado y para que se vea que se puede cerrar.
                            -->
                            <div
                                v-if="hasBack"
                                class="scroll-suave-claro absolute inset-0 flex cursor-pointer flex-col overflow-y-auto rounded-t-xl bg-gradient-to-br from-brand-sky to-brand-sky-dark p-6 text-white [backface-visibility:hidden] [transform:rotateY(180deg)] md:rounded-l-xl md:rounded-tr-none md:p-8"
                                :aria-hidden="!flipped"
                                @click="closeUnlessSelecting"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-display text-2xl uppercase tracking-wide md:text-3xl">
                                        {{ flipsBody ? (section.content.heading ?? 'Descripción') : 'Clases del ciclo' }}
                                    </h3>
                                    <button
                                        type="button"
                                        class="flex shrink-0 items-center gap-1 rounded-full bg-white/20 px-3 py-1.5 text-xs font-medium uppercase tracking-wide transition hover:bg-white/30"
                                        :tabindex="flipped ? 0 : -1"
                                        @click="flipped = false"
                                    >
                                        <RotateCcw class="h-3.5 w-3.5" /> volver
                                    </button>
                                </div>

                                <div v-if="flipsBody" class="mt-4 space-y-3 text-[15px] leading-relaxed text-white/95">
                                    <p v-for="(p, i) in bodyParagraphs" :key="i" class="whitespace-pre-line">{{ p }}</p>
                                </div>

                                <h4
                                    v-if="flipsBody && cycle.length"
                                    class="mt-6 border-t border-white/25 pt-4 font-display text-lg uppercase tracking-wide"
                                >
                                    Clases del ciclo
                                </h4>

                                <ol v-if="cycle.length" class="mt-5 space-y-3">
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
                        class="flex flex-col justify-center px-8 py-4 md:px-10 md:py-5"
                        :class="[
                            !section.content.image ? 'md:col-span-2' : '',
                            section.content.image && !expanded && !flipsBody ? 'md:absolute md:inset-y-0 md:right-0 md:w-1/2 md:overflow-hidden' : '',
                        ]"
                    >
                        <!--
                            El maestro va dentro del h2 para que fluya después del
                            título (que puede tener varios renglones), más chico y en
                            naranja para que se lea como un dato aparte y no como
                            parte del nombre de la actividad.
                        -->
                        <h2 class="shrink-0 whitespace-pre-line font-heading text-3xl font-light leading-snug text-brand-sky md:text-[32px]">
                            <!--
                                El espacio va dentro del span y es duro: si fuera sólo
                                el margen, un lector de pantalla (y quien copie el
                                texto) leería "Libertad emocionalcon Kelsang Panchen",
                                y "con" no debe quedar solo al final de un renglón.
                            -->
                            {{ section.content.heading
                            }}<span v-if="teachers" class="ml-0.5 whitespace-normal text-base font-normal text-brand-orange md:text-lg"
                                >&nbsp;con {{ teachers }}</span
                            >
                        </h2>

                        <!--
                            Con scroll, el tabindex deja desplazarlo con el teclado, y el
                            degradado va sticky para quedarse al pie mientras el texto se
                            mueve (absolute se iría con el contenido).
                        -->
                        <div
                            v-if="bodyParagraphs.length && !flipsBody"
                            ref="body"
                            class="relative min-h-0"
                            :class="scrolls ? 'scroll-suave overflow-y-auto overscroll-contain pr-2 focus:outline-none' : 'overflow-hidden'"
                            :tabindex="scrolls && clipped ? 0 : undefined"
                            :aria-label="scrolls && clipped ? 'Descripción' : undefined"
                            @scroll="scrolls && measure()"
                        >
                            <p v-for="(p, i) in bodyParagraphs" :key="i" class="mt-4 whitespace-pre-line leading-relaxed text-brand-body">
                                {{ p }}
                            </p>

                            <div
                                v-if="scrolls && clipped && !atEnd"
                                class="pointer-events-none sticky bottom-0 -mt-10 h-10 bg-gradient-to-t from-brand-cream to-transparent"
                                aria-hidden="true"
                            ></div>

                            <!-- Degradado para que el corte no quede a mitad de renglón. -->
                            <div
                                v-else-if="!scrolls && clipped && !expanded"
                                class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-brand-cream to-transparent"
                                aria-hidden="true"
                            ></div>
                        </div>

                        <button
                            v-if="!scrolls && (clipped || expanded)"
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
