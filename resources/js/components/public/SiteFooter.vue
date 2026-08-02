<script setup lang="ts">
import { img, type CardItem } from '@/lib/site';
import { usePage } from '@inertiajs/vue3';
import { Instagram, Mail, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const settings = computed(() => (page.props.settings ?? {}) as Record<string, any>);
const resources = computed(() => (settings.value.footer_resources ?? []) as CardItem[]);

/** El pie puede tener su propio logo; si no se cargó, usa el del menú. */
const logo = computed(() => settings.value.footer_logo_path || settings.value.logo_path);
</script>

<template>
    <footer class="mt-16">
        <div v-if="resources.length" class="border-t border-brand-line/60 bg-brand-cream/60">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:grid-cols-2 lg:grid-cols-3">
                <a
                    v-for="(card, i) in resources"
                    :key="i"
                    :href="card.url ?? undefined"
                    target="_blank"
                    rel="noopener"
                    class="group flex flex-col items-center text-center"
                >
                    <img
                        v-if="card.image"
                        :src="img(card.image)"
                        :alt="card.title ?? ''"
                        class="mb-4 h-48 w-auto object-contain transition group-hover:scale-105"
                    />
                    <h3 class="font-heading text-lg font-semibold tracking-wide text-brand-ink">{{ card.title }}</h3>
                    <p v-if="card.text" class="mt-1 text-sm text-brand-muted">{{ card.text }}</p>
                </a>
            </div>
        </div>

        <div class="bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center gap-6 border-t border-brand-line/60 px-4 py-10">
                <img v-if="logo" :src="img(logo)" alt="Logo" class="h-24 w-24 object-contain" />

                <div class="flex flex-wrap items-center justify-center gap-6 text-sm text-brand-body">
                    <a v-if="settings.email" :href="`mailto:${settings.email}`" class="flex items-center gap-2 transition hover:text-brand-sky">
                        <Mail class="h-4 w-4" /> {{ settings.email }}
                    </a>
                    <a
                        v-if="settings.phone_display"
                        :href="settings.phone_link ?? undefined"
                        class="flex items-center gap-2 transition hover:text-brand-sky"
                    >
                        <Phone class="h-4 w-4" /> {{ settings.phone_display }}
                    </a>
                    <a
                        v-if="settings.instagram_url"
                        :href="settings.instagram_url"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2 transition hover:text-brand-sky"
                    >
                        <Instagram class="h-4 w-4" /> Instagram
                    </a>
                </div>

                <p v-if="settings.address" class="text-sm text-brand-muted">{{ settings.address }}</p>
                <p class="text-xs text-brand-muted">© {{ new Date().getFullYear() }} {{ settings.site_name }}</p>
            </div>
        </div>
    </footer>
</template>
