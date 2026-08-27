<script setup lang="ts">
import { paragraphs, type FaqItem, type SectionData } from '@/lib/site';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData; faqs?: Record<number, FaqItem> }>();

const items = computed(() =>
    ((props.section.content.faq_ids ?? []) as number[])
        .map((id) => props.faqs?.[id])
        .filter((faq): faq is FaqItem => !!faq),
);
</script>

<template>
    <section v-if="items.length">
        <div class="mx-auto max-w-3xl px-4">
            <h2
                v-if="section.content.heading"
                class="mb-8 text-center font-heading text-3xl font-light text-brand-sky md:text-[35px]"
            >
                {{ section.content.heading }}
            </h2>

            <div class="divide-y divide-brand-line/60 border-y border-brand-line/60">
                <details v-for="(faq, i) in items" :key="i" class="group">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 font-medium text-brand-ink transition hover:text-brand-sky [&::-webkit-details-marker]:hidden"
                    >
                        {{ faq.question }}
                        <ChevronDown class="h-5 w-5 shrink-0 text-brand-sky transition group-open:rotate-180" />
                    </summary>
                    <div class="pb-5">
                        <p v-for="(p, j) in paragraphs(faq.answer)" :key="j" class="mt-2 leading-relaxed text-brand-body first:mt-0">
                            {{ p }}
                        </p>
                    </div>
                </details>
            </div>
        </div>
    </section>
</template>
