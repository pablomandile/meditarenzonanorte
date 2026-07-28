<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItemType[] }>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string | null });

const toast = ref<string | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;

watch(
    () => flash.value.success,
    (message) => {
        if (!message) return;
        toast.value = message;
        clearTimeout(timer);
        timer = setTimeout(() => (toast.value = null), 3500);
    },
    { immediate: true },
);
</script>

<template>
    <AppLayout :breadcrumbs="props.breadcrumbs">
        <slot />

        <ConfirmDialog />

        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-300"
            leave-to-class="opacity-0"
        >
            <div
                v-if="toast"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-sm font-medium text-white shadow-lg"
            >
                <CheckCircle2 class="h-5 w-5" />
                {{ toast }}
            </div>
        </Transition>
    </AppLayout>
</template>
