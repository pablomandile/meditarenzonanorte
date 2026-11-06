<script setup lang="ts">
import { isInternal, mapsUrl, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { computed, inject } from 'vue';

const props = defineProps<{ section: SectionData }>();
const c = computed(() => props.section.content);

/** En la vista previa del panel el botón se dibuja aunque falte la URL, para ver el diseño. */
const preview = inject<boolean>('landingPreview', false);

const prices = computed<{ note: string | null; amount: string | null }[]>(() => c.value.prices ?? []);
const hasButton = computed(() => !!c.value.cta_label && (!!c.value.cta_url || preview));
const showPrice = computed(() => prices.value.length > 0 || hasButton.value);

/** La búsqueda del mapa: la escrita a mano, o el lugar y la dirección. */
const mapQuery = computed<string>(
    () =>
        c.value.map_query ||
        [c.value.venue, c.value.address]
            .filter(Boolean)
            .join(', ')
            .replace(/\s*\n\s*/g, ' '),
);
const embed = computed(() => (mapQuery.value ? `https://maps.google.com/maps?q=${encodeURIComponent(mapQuery.value)}&output=embed` : null));
const showPlace = computed(() => !!(c.value.venue || c.value.address));
</script>

<template>
    <section id="inscripcion" class="lp-details">
        <div class="lp-details-heading">
            <p v-if="c.kicker" class="lp-kicker">{{ c.kicker }}</p>
            <h2 v-if="c.heading">{{ c.heading }}</h2>
            <p v-if="c.intro">{{ c.intro }}</p>
        </div>

        <div v-if="showPrice || showPlace" class="lp-details-grid" :class="{ 'lp-details-grid-1': !(showPrice && showPlace) }">
            <div v-if="showPrice" class="lp-detail-card lp-price-card">
                <span v-if="c.price_label" class="lp-detail-label">{{ c.price_label }}</span>
                <template v-for="(price, i) in prices" :key="i">
                    <div v-if="i > 0" class="lp-price-divider"></div>
                    <p v-if="price.note" class="lp-price-note">{{ price.note }}</p>
                    <div v-if="price.amount" class="lp-price" :class="{ 'lp-price-later': i > 0 }">{{ price.amount }}</div>
                </template>
                <template v-if="hasButton">
                    <Link v-if="c.cta_url && isInternal(c.cta_url)" :href="c.cta_url" class="lp-button lp-button-primary">{{ c.cta_label }}</Link>
                    <a v-else-if="c.cta_url" :href="c.cta_url" target="_blank" rel="noopener" class="lp-button lp-button-primary">{{
                        c.cta_label
                    }}</a>
                    <span v-else class="lp-button lp-button-primary" title="Falta la URL del botón">{{ c.cta_label }}</span>
                </template>
            </div>

            <div v-if="showPlace" class="lp-detail-card">
                <span class="lp-detail-icon" aria-hidden="true">⌖</span>
                <span v-if="c.place_label" class="lp-detail-label">{{ c.place_label }}</span>
                <h3 v-if="c.venue">{{ c.venue }}</h3>
                <p v-if="c.address">{{ c.address }}</p>
                <iframe
                    v-if="embed"
                    :src="embed"
                    class="lp-map"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    :title="`Mapa: ${mapQuery}`"
                ></iframe>
                <a v-if="mapQuery" :href="mapsUrl(mapQuery)" class="lp-text-link" target="_blank" rel="noopener">Ver ubicación en el mapa ↗</a>
            </div>
        </div>
    </section>
</template>
