<script setup lang="ts">
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Download } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const isIos = computed(() => /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.userAgent.includes('Mac') && navigator.maxTouchPoints > 1));

const isStandalone = computed(
    () => window.matchMedia('(display-mode: standalone)').matches || (navigator as any).standalone === true,
);

const canInstall = ref(false);
const showIosGuide = ref(false);
const showIosModal = ref(false);

function refresh() {
    if (isStandalone.value) return;
    if (isIos.value) {
        showIosGuide.value = true;
    } else {
        canInstall.value = Boolean(window.__pwaInstall?.prompt);
    }
}

function onInstallable() { refresh(); }
function onInstalled() { canInstall.value = false; showIosGuide.value = false; }

onMounted(() => {
    refresh();
    window.addEventListener('pwa:installable', onInstallable);
    window.addEventListener('pwa:installed', onInstalled);
});

onUnmounted(() => {
    window.removeEventListener('pwa:installable', onInstallable);
    window.removeEventListener('pwa:installed', onInstalled);
});

const visible = computed(() => !isStandalone.value && (canInstall.value || showIosGuide.value));

async function install() {
    const p = window.__pwaInstall?.prompt;
    if (!p) {
        // iOS o prompt ya consumido: mostrar instrucciones manuales.
        showIosModal.value = true;
        return;
    }
    await p.prompt();
    window.__pwaInstall.prompt = null;
    canInstall.value = false;
}
</script>

<template>
    <!-- md:hidden: el botón solo tiene sentido en móvil -->
    <div v-if="visible" class="md:hidden">
        <SidebarGroup class="group-data-[collapsible=icon]:p-0">
            <SidebarGroupContent>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                            @click="install"
                        >
                            <Download />
                            <span>Instalar app</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    </div>

    <!-- Modal con instrucciones para iOS -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showIosModal"
                class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 px-4 pb-8"
                @click.self="showIosModal = false"
            >
                <div class="w-full max-w-sm rounded-2xl bg-white px-6 pb-6 pt-5 shadow-xl">
                    <p class="text-sm font-semibold text-gray-900">Instalar en iPhone / iPad</p>
                    <p class="mt-2 text-sm text-gray-600">
                        Tocá
                        <svg class="mx-0.5 inline h-4 w-4 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M8 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-4" />
                            <polyline points="16 6 12 2 8 6" />
                            <line x1="12" y1="2" x2="12" y2="15" />
                        </svg>
                        y luego <strong>«Agregar a inicio»</strong> para instalar la app en tu teléfono.
                    </p>
                    <button
                        type="button"
                        class="mt-4 w-full rounded-lg bg-gray-900 py-2 text-sm font-medium text-white active:scale-95"
                        @click="showIosModal = false"
                    >
                        Entendido
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
