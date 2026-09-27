<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ breadcrumbs?: BreadcrumbItemType[] }>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string | null; error?: string | null });

const toast = ref<{ message: string; variant: 'success' | 'error' } | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;

/**
 * El error se queda bastante más que el éxito: uno confirma algo que ya se vio pasar
 * y el otro hay que llegar a leerlo, que a veces es un mensaje largo de Mailchimp.
 */
function show(message: string | null | undefined, variant: 'success' | 'error') {
    if (!message) return;
    toast.value = { message, variant };
    clearTimeout(timer);
    timer = setTimeout(() => (toast.value = null), variant === 'error' ? 9000 : 3500);
}

watch(
    () => flash.value.success,
    (message) => show(message, 'success'),
    { immediate: true },
);
watch(
    () => flash.value.error,
    (message) => show(message, 'error'),
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
                class="fixed bottom-6 right-6 z-50 flex max-w-md items-start gap-2 rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg"
                :class="toast.variant === 'error' ? 'bg-red-600' : 'bg-emerald-600'"
                @click="toast = null"
            >
                <component :is="toast.variant === 'error' ? XCircle : CheckCircle2" class="mt-0.5 h-5 w-5 shrink-0" />
                {{ toast.message }}
            </div>
        </Transition>
    </AppLayout>
</template>
