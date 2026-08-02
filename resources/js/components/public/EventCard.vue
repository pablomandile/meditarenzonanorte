<script setup lang="ts">
import { img, isInternal, type EventData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { Calendar, Clock } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(defineProps<{ event: EventData; featured?: boolean }>(), { featured: false });

/**
 * El afiche puede llevar a un lado y el botón a otro (la publicación por un lado,
 * la inscripción por el otro). Sin URL propia, la imagen sigue al botón, y sin
 * ninguna de las dos cae en la página de eventos, como hacía antes del botón.
 */
const target = computed(() => props.event.image_url || props.event.cta_url || '/eventos-especiales');

/** El <a> externo y el <Link> interno de Inertia no se intercambian por props. */
const wrapper = computed(() => (isInternal(target.value) ? Link : 'a'));
const wrapperAttrs = computed(() =>
    isInternal(target.value) ? { href: target.value } : { href: target.value, target: '_blank', rel: 'noopener' },
);
</script>

<template>
    <article class="group flex flex-col overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-brand-line/50">
        <!--
            La imagen y el título son dos enlaces al mismo destino, en lugar de una
            tarjeta envuelta entera: el botón de abajo es otro enlace, y un <a>
            dentro de otro <a> no es HTML válido.
        -->
        <component :is="wrapper" v-if="event.image_path" v-bind="wrapperAttrs" class="block overflow-hidden">
            <img
                :src="img(event.image_path)"
                :alt="event.title"
                class="aspect-square w-full object-cover transition duration-300 group-hover:scale-[1.03]"
            />
        </component>

        <div class="flex flex-1 flex-col" :class="featured ? 'p-7' : 'p-5'">
            <component :is="wrapper" v-bind="wrapperAttrs" class="block">
                <h3
                    class="font-heading font-semibold leading-snug text-brand-ink group-hover:text-brand-sky"
                    :class="featured ? 'text-2xl md:text-3xl' : 'text-lg'"
                >
                    {{ event.title }}
                </h3>
                <p
                    v-if="event.date_label"
                    class="mt-2 flex items-center gap-2 text-brand-muted"
                    :class="featured ? 'text-base md:text-lg' : 'text-sm'"
                >
                    <Calendar class="shrink-0 text-brand-orange" :class="featured ? 'h-5 w-5' : 'h-4 w-4'" />
                    {{ event.date_label }}
                </p>
            </component>

            <div v-if="event.cta_label" class="mt-4">
                <Link
                    v-if="event.cta_url && isInternal(event.cta_url)"
                    :href="event.cta_url"
                    class="inline-block rounded-full bg-brand-sky font-medium uppercase tracking-wide text-white transition hover:bg-brand-sky-dark"
                    :class="featured ? 'px-6 py-2.5 text-sm' : 'px-5 py-2 text-xs'"
                >
                    {{ event.cta_label }}
                </Link>
                <a
                    v-else-if="event.cta_url"
                    :href="event.cta_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-block rounded-full bg-brand-sky font-medium uppercase tracking-wide text-white transition hover:bg-brand-sky-dark"
                    :class="featured ? 'px-6 py-2.5 text-sm' : 'px-5 py-2 text-xs'"
                >
                    {{ event.cta_label }}
                </a>
                <!-- Con texto de botón pero sin URL todavía (ej. "inscripción próximamente"). -->
                <span
                    v-else
                    class="inline-flex items-center gap-2 rounded-full bg-brand-line/50 font-medium uppercase tracking-wide text-brand-muted"
                    :class="featured ? 'px-6 py-2.5 text-sm' : 'px-5 py-2 text-xs'"
                >
                    <Clock :class="featured ? 'h-4 w-4' : 'h-3.5 w-3.5'" /> {{ event.cta_label }}
                </span>
            </div>
        </div>
    </article>
</template>
