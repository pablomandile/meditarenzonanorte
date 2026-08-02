<script setup lang="ts">
import { timeRange, type CalendarActivityData, type SourceStyle } from '@/lib/calendar';
import { isInternal, mapsUrl } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Clock, MapPin, Ticket } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ activity: CalendarActivityData; style: SourceStyle }>();

/** Con hora estructurada se arma el rango; si no, se cae al texto que escribió el dueño. */
const hours = computed(() => timeRange(props.activity.start, props.activity.end) || props.activity.time_text || null);
</script>

<template>
    <div class="min-w-0">
        <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide" :class="style.label">
            <component :is="style.icon" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            <span class="truncate">{{ activity.source.title }}</span>
        </p>

        <p class="mt-0.5 font-heading text-[15px] font-semibold leading-snug text-brand-ink">{{ activity.title }}</p>

        <ul class="mt-1.5 space-y-1 text-sm text-brand-body">
            <li v-if="hours" class="flex items-start gap-2">
                <Clock class="mt-0.5 h-4 w-4 shrink-0 text-brand-orange" />
                <time v-if="activity.start" :datetime="activity.start" class="whitespace-pre-line font-medium">{{ hours }}</time>
                <span v-else class="whitespace-pre-line font-medium">{{ hours }}</span>
            </li>
            <li v-if="activity.location" class="flex items-start gap-2">
                <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-brand-orange" />
                <a
                    :href="mapsUrl(activity.location)"
                    target="_blank"
                    rel="noopener"
                    title="Ver en Google Maps"
                    class="underline decoration-brand-muted/60 underline-offset-2 transition hover:text-brand-sky hover:decoration-brand-sky"
                >
                    {{ activity.location }}
                </a>
            </li>
            <li v-if="activity.price" class="flex items-start gap-2">
                <Ticket class="mt-0.5 h-4 w-4 shrink-0 text-brand-orange" />
                <span class="font-semibold">{{ activity.price }}</span>
            </li>
        </ul>

        <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-2">
            <template v-if="activity.cta_label && activity.cta_url">
                <Link
                    v-if="isInternal(activity.cta_url)"
                    :href="activity.cta_url"
                    class="inline-block rounded-full bg-brand-orange px-4 py-1.5 text-xs font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                >
                    {{ activity.cta_label }}
                </Link>
                <a
                    v-else
                    :href="activity.cta_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-block rounded-full bg-brand-orange px-4 py-1.5 text-xs font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                >
                    {{ activity.cta_label }}
                </a>
            </template>

            <Link
                v-if="activity.source.url"
                :href="activity.source.url"
                class="inline-flex items-center gap-1 text-xs font-medium text-brand-sky hover:underline"
            >
                ver más <ArrowRight class="h-3 w-3" />
            </Link>
        </div>
    </div>
</template>
