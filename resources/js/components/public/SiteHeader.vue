<script setup lang="ts">
import { img } from '@/lib/site';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, Phone, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const nav = computed(() => (page.props.nav ?? []) as { slug: string; label: string }[]);
const settings = computed(() => (page.props.settings ?? {}) as Record<string, any>);
const currentUrl = computed(() => page.url);

const open = ref(false);
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-brand-line/60 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-20 max-w-6xl items-center justify-between gap-4 px-4">
            <Link href="/" class="flex shrink-0 items-center gap-3" aria-label="Inicio">
                <img v-if="settings.logo_path" :src="img(settings.logo_path)" alt="Logo" class="h-14 w-14 object-contain" />
                <span v-else class="font-display text-xl text-brand-ink">{{ settings.site_name }}</span>
            </Link>

            <nav class="hidden items-center gap-6 lg:flex">
                <Link
                    v-for="item in nav"
                    :key="item.slug"
                    :href="`/${item.slug}`"
                    class="text-[15px] font-medium text-brand-ink transition hover:text-brand-sky"
                    :class="{ 'text-brand-sky': currentUrl.startsWith(`/${item.slug}`) }"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <div class="flex items-center gap-3">
                <a
                    v-if="settings.phone_display"
                    :href="settings.phone_link ?? undefined"
                    class="hidden items-center gap-2 rounded-full bg-brand-sky px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-sky-dark sm:flex"
                >
                    <Phone class="h-4 w-4" />
                    {{ settings.phone_display }}
                </a>

                <button
                    type="button"
                    class="rounded-md p-2 text-brand-ink hover:bg-brand-light lg:hidden"
                    :aria-expanded="open"
                    aria-label="Abrir menú"
                    @click="open = !open"
                >
                    <X v-if="open" class="h-6 w-6" />
                    <Menu v-else class="h-6 w-6" />
                </button>
            </div>
        </div>

        <nav v-if="open" class="border-t border-brand-line/60 bg-white px-4 pb-4 lg:hidden">
            <Link
                v-for="item in nav"
                :key="item.slug"
                :href="`/${item.slug}`"
                class="block border-b border-brand-line/40 py-3 font-medium text-brand-ink hover:text-brand-sky"
                @click="open = false"
            >
                {{ item.label }}
            </Link>
            <a
                v-if="settings.phone_display"
                :href="settings.phone_link ?? undefined"
                class="mt-4 flex w-fit items-center gap-2 rounded-full bg-brand-sky px-4 py-2 text-sm font-medium text-white"
            >
                <Phone class="h-4 w-4" />
                {{ settings.phone_display }}
            </a>
        </nav>
    </header>
</template>
