<script setup lang="ts">
import { img, isInternal, paragraphs, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';

defineProps<{ section: SectionData }>();
</script>

<template>
    <section>
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 md:grid-cols-2">
            <div :class="{ 'md:order-2': section.content.image_side !== 'left' }">
                <img
                    v-if="section.content.image"
                    :src="img(section.content.image)"
                    :alt="section.content.heading ?? ''"
                    class="mx-auto w-full max-w-md rounded-lg object-cover shadow-sm"
                />
            </div>

            <div :class="{ 'md:order-1': section.content.image_side !== 'left' }">
                <h2
                    v-if="section.content.heading"
                    class="font-heading text-3xl font-light text-brand-sky md:text-[35px] md:leading-snug"
                >
                    {{ section.content.heading }}
                </h2>

                <p v-for="(p, i) in paragraphs(section.content.body)" :key="i" class="mt-4 leading-relaxed text-brand-body">
                    {{ p }}
                </p>

                <div v-if="section.content.link_label && section.content.link_url" class="mt-6">
                    <Link
                        v-if="isInternal(section.content.link_url)"
                        :href="section.content.link_url"
                        class="inline-block rounded-full bg-brand-orange px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                    >
                        {{ section.content.link_label }}
                    </Link>
                    <a
                        v-else
                        :href="section.content.link_url"
                        target="_blank"
                        rel="noopener"
                        class="inline-block rounded-full bg-brand-orange px-6 py-2.5 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                    >
                        {{ section.content.link_label }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
