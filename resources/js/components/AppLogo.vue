<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { img } from '@/lib/site';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const settings = computed(() => (page.props.settings ?? {}) as Record<string, any>);
const logo = computed(() => img(settings.value.logo_path));
</script>

<template>
    <div class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white ring-1 ring-sidebar-border">
        <img v-if="logo" :src="logo" alt="Logo" class="size-7 object-contain" />
        <AppLogoIcon v-else class="size-5 fill-current text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none">{{ settings.site_name || 'Meditar en Rosario' }}</span>
    </div>
</template>
