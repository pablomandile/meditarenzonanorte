<script setup lang="ts">
import { img, isInternal, paragraphs, type LinkItem, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();

const links = computed(() => ((props.section.content.links ?? []) as LinkItem[]).filter((l) => l.label && l.url));
const buttonLinks = computed(() => links.value.filter((l) => isInternal(l.url)));
const textLinks = computed(() => links.value.filter((l) => !isInternal(l.url)));
const hasImage = computed(() => !!props.section.content.image);
</script>

<template>
    <section class="py-12 md:py-16">
        <div class="mx-auto max-w-6xl px-4">
            <div :class="hasImage ? 'grid items-center gap-10 md:grid-cols-2' : 'mx-auto max-w-3xl text-center'">
                <div :class="{ 'md:order-2': hasImage && section.content.image_side !== 'left' }">
                    <img
                        v-if="hasImage"
                        :src="img(section.content.image)"
                        :alt="section.content.heading ?? ''"
                        class="w-full rounded-lg object-cover shadow-sm"
                    />
                </div>

                <div :class="{ 'md:order-1': hasImage && section.content.image_side !== 'left' }">
                    <h2
                        v-if="section.content.heading"
                        class="font-heading text-3xl font-light text-brand-sky md:text-[35px] md:leading-snug"
                    >
                        {{ section.content.heading }}
                    </h2>

                    <p
                        v-for="(p, i) in paragraphs(section.content.body)"
                        :key="i"
                        class="mt-4 leading-relaxed text-brand-body"
                    >
                        {{ p }}
                    </p>

                    <div v-if="textLinks.length" class="mt-4 flex flex-wrap gap-x-5 gap-y-2" :class="{ 'justify-center': !hasImage }">
                        <a
                            v-for="(link, i) in textLinks"
                            :key="i"
                            :href="link.url ?? undefined"
                            target="_blank"
                            rel="noopener"
                            class="font-medium text-brand-sky underline-offset-4 hover:underline"
                        >
                            {{ link.label }}
                        </a>
                    </div>

                    <div v-if="buttonLinks.length" class="mt-6 flex flex-wrap gap-3" :class="{ 'justify-center': !hasImage }">
                        <Link
                            v-for="(link, i) in buttonLinks"
                            :key="i"
                            :href="link.url as string"
                            class="rounded-full bg-brand-orange px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                        >
                            {{ link.label }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
