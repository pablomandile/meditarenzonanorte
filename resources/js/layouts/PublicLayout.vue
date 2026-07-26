<script setup lang="ts">
import SiteFooter from '@/components/public/SiteFooter.vue';
import SiteHeader from '@/components/public/SiteHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const settings = computed(() => (page.props.settings ?? {}) as Record<string, any>);
</script>

<template>
    <div class="min-h-screen bg-white font-sans text-brand-body antialiased">
        <SiteHeader />

        <main>
            <slot />
        </main>

        <SiteFooter />

        <a
            v-if="settings.whatsapp_url"
            :href="settings.whatsapp_url"
            target="_blank"
            rel="noopener"
            aria-label="WhatsApp"
            class="fixed bottom-5 right-5 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition hover:scale-105"
        >
            <svg viewBox="0 0 32 32" class="h-8 w-8 fill-current" aria-hidden="true">
                <path
                    d="M16.004 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.257.59 4.462 1.71 6.402L3.2 28.8l6.58-1.672a12.73 12.73 0 0 0 6.222 1.606h.006c7.058 0 12.798-5.74 12.798-12.8s-5.74-12.734-12.802-12.734zm0 23.398h-.005a10.6 10.6 0 0 1-5.4-1.48l-.387-.23-3.905.993 1.043-3.807-.252-.39a10.55 10.55 0 0 1-1.628-5.684c0-5.86 4.772-10.628 10.639-10.628 2.84 0 5.51 1.107 7.517 3.117a10.56 10.56 0 0 1 3.112 7.52c-.003 5.86-4.775 10.59-10.734 10.59zm5.834-7.955c-.32-.16-1.888-.932-2.18-1.038-.293-.107-.506-.16-.72.16-.213.32-.825 1.037-1.011 1.25-.187.214-.373.24-.693.08-.32-.16-1.348-.497-2.568-1.584-.95-.847-1.59-1.894-1.777-2.214-.187-.32-.02-.493.14-.652.144-.144.32-.374.48-.56.16-.187.213-.32.32-.534.107-.213.053-.4-.027-.56-.08-.16-.719-1.734-.985-2.374-.26-.624-.523-.54-.72-.55l-.612-.01c-.213 0-.56.08-.853.4-.293.32-1.119 1.094-1.119 2.667s1.146 3.094 1.306 3.307c.16.214 2.255 3.444 5.464 4.83.764.33 1.36.526 1.824.673.767.244 1.464.21 2.015.127.615-.092 1.888-.772 2.155-1.518.266-.746.266-1.386.186-1.519-.08-.133-.293-.213-.613-.373z"
                />
            </svg>
        </a>
    </div>
</template>
