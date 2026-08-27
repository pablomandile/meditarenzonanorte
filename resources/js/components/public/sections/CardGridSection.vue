<script setup lang="ts">
import { img, isInternal, type CardItem, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();

const cards = computed(() => ((props.section.content.cards ?? []) as CardItem[]).filter((c) => c.image || c.title));
const cols = computed(() => Math.min(cards.value.length, 3));
</script>

<template>
    <section>
        <div class="mx-auto max-w-6xl px-4">
            <h2
                v-if="section.content.heading"
                class="mb-10 text-center font-heading text-3xl font-light text-brand-sky md:text-[35px]"
            >
                {{ section.content.heading }}
            </h2>

            <div
                class="grid gap-8"
                :class="{
                    'sm:grid-cols-1': cols === 1,
                    'sm:grid-cols-2': cols === 2,
                    'sm:grid-cols-2 lg:grid-cols-3': cols >= 3,
                }"
            >
                <component
                    :is="card.url ? (isInternal(card.url) ? Link : 'a') : 'div'"
                    v-for="(card, i) in cards"
                    :key="i"
                    :href="card.url ?? undefined"
                    v-bind="card.url && !isInternal(card.url) ? { target: '_blank', rel: 'noopener' } : {}"
                    class="group block overflow-hidden rounded-lg"
                >
                    <div v-if="card.image" class="overflow-hidden rounded-lg">
                        <img
                            :src="img(card.image)"
                            :alt="card.title ?? ''"
                            class="w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                        />
                    </div>
                    <div v-if="card.title || card.text" class="pt-4 text-center">
                        <h3 class="font-heading text-xl font-semibold uppercase tracking-wide text-brand-ink">
                            {{ card.title }}
                        </h3>
                        <p v-if="card.text" class="mt-1 text-sm text-brand-muted">{{ card.text }}</p>
                    </div>
                </component>
            </div>
        </div>
    </section>
</template>
