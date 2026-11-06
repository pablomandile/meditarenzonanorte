<script setup lang="ts">
import { img, isInternal, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();
const c = computed(() => props.section.content);

/** El mismo degradé oscuro de la página de ejemplo, sobre la imagen. */
const background = computed(() => {
    const gradient = 'linear-gradient(90deg, rgba(24,31,47,.82) 0%, rgba(24,31,47,.52) 48%, rgba(24,31,47,.08) 100%)';
    const image = img(c.value.image);

    return { backgroundImage: image ? `${gradient}, url("${image}")` : gradient };
});

/** Sin URL, el botón baja a "Reserva tu lugar", como en la página de ejemplo. */
const href = computed(() => c.value.cta_url || '#inscripcion');
const external = computed(() => !!c.value.cta_url && !isInternal(c.value.cta_url));
</script>

<template>
    <section class="lp-hero" :style="background">
        <div class="lp-hero-content">
            <p v-if="c.eyebrow" class="lp-eyebrow">{{ c.eyebrow }}</p>
            <h1>
                {{ c.heading
                }}<template v-if="c.heading_accent"
                    ><br v-if="c.heading" /><span>{{ c.heading_accent }}</span></template
                >
            </h1>
            <p v-if="c.subheading" class="lp-hero-subtitle">{{ c.subheading }}</p>
            <div v-if="c.date || c.place" class="lp-hero-meta">
                <span v-if="c.date">{{ c.date }}</span>
                <span v-if="c.date && c.place" class="lp-meta-dot">•</span>
                <span v-if="c.place">{{ c.place }}</span>
            </div>
            <template v-if="c.cta_label">
                <Link v-if="c.cta_url && isInternal(c.cta_url)" :href="c.cta_url" class="lp-button lp-button-light">{{ c.cta_label }}</Link>
                <a v-else :href="href" class="lp-button lp-button-light" v-bind="external ? { target: '_blank', rel: 'noopener' } : {}">
                    {{ c.cta_label }}
                </a>
            </template>
        </div>
    </section>
</template>
