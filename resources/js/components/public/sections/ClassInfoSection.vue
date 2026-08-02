<script setup lang="ts">
import { img, isInternal, paragraphs, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { ChevronDown, Clock, MapPin, Ticket } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

defineProps<{ section: SectionData }>();

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
                    <div v-if="section.content.image">
                        <img
                            :src="img(section.content.image)"
                            :alt="section.content.heading ?? ''"
                            class="h-auto w-full rounded-t-xl md:rounded-l-xl md:rounded-tr-none"
                        />
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
                                <span>{{ section.content.location }}</span>
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
