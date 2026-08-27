<script setup lang="ts">
import { isInternal, paragraphs, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { Check } from 'lucide-vue-next';
import { computed } from 'vue';

type Plan = {
    name?: string | null;
    price?: string | null;
    period?: string | null;
    features?: string | null;
    note?: string | null;
    highlighted?: boolean | null;
};

const props = defineProps<{ section: SectionData }>();

const plans = computed(() => ((props.section.content.plans ?? []) as Plan[]).filter((p) => p.name || p.price));

function features(plan: Plan): string[] {
    return (plan.features ?? '')
        .split('\n')
        .map((f) => f.trim())
        .filter(Boolean);
}
</script>

<template>
    <section>
        <div class="mx-auto max-w-6xl px-4">
            <div class="mx-auto max-w-3xl text-center">
                <h2 v-if="section.content.heading" class="font-heading text-3xl font-light text-brand-sky md:text-[35px]">
                    {{ section.content.heading }}
                </h2>
                <p v-for="(p, i) in paragraphs(section.content.intro)" :key="i" class="mt-4 leading-relaxed text-brand-body">
                    {{ p }}
                </p>
            </div>

            <div
                class="mt-10 grid gap-6"
                :class="{
                    'sm:grid-cols-2 lg:grid-cols-3': plans.length >= 3,
                    'sm:grid-cols-2': plans.length === 2,
                    'mx-auto max-w-sm': plans.length === 1,
                }"
            >
                <div
                    v-for="(plan, i) in plans"
                    :key="i"
                    class="relative flex flex-col rounded-2xl border p-7 transition"
                    :class="
                        plan.highlighted
                            ? 'border-brand-orange bg-brand-cream shadow-lg lg:-translate-y-2'
                            : 'border-brand-line/70 bg-white shadow-sm'
                    "
                >
                    <span
                        v-if="plan.highlighted"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-orange px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white"
                    >
                        Más elegida
                    </span>

                    <h3 class="font-heading text-xl font-semibold text-brand-ink">{{ plan.name }}</h3>

                    <div class="mt-3 flex items-baseline gap-1">
                        <span class="font-display text-4xl text-brand-sky">{{ plan.price }}</span>
                        <span v-if="plan.period" class="text-sm text-brand-muted">{{ plan.period }}</span>
                    </div>

                    <ul class="mt-6 flex-1 space-y-3">
                        <li v-for="(feature, fi) in features(plan)" :key="fi" class="flex items-start gap-2.5 text-sm text-brand-body">
                            <Check class="mt-0.5 h-4 w-4 shrink-0 text-brand-orange" />
                            <span>{{ feature }}</span>
                        </li>
                    </ul>

                    <p
                        v-if="plan.note"
                        class="mt-6 rounded-lg bg-brand-light/70 px-3 py-2 text-center text-xs font-medium text-brand-sky-dark"
                    >
                        {{ plan.note }}
                    </p>
                </div>
            </div>

            <p
                v-if="section.content.footnote"
                class="mx-auto mt-10 max-w-2xl text-center text-sm leading-relaxed text-brand-muted"
            >
                {{ section.content.footnote }}
            </p>

            <div v-if="section.content.cta_label && section.content.cta_url" class="mt-8 text-center">
                <Link
                    v-if="isInternal(section.content.cta_url)"
                    :href="section.content.cta_url"
                    class="inline-block rounded-full bg-brand-orange px-8 py-3 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                >
                    {{ section.content.cta_label }}
                </Link>
                <a
                    v-else
                    :href="section.content.cta_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-block rounded-full bg-brand-orange px-8 py-3 text-sm font-medium uppercase tracking-wide text-white transition hover:bg-brand-orange-dark"
                >
                    {{ section.content.cta_label }}
                </a>
            </div>
        </div>
    </section>
</template>
