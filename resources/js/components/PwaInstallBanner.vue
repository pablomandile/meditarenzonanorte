<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Download, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();

// Logo real desde los ajustes del sitio — misma lógica que Setting::favicon():
// prefiere footer_logo_path (isotipo cuadrado), cae a logo_path, cae al placeholder.
const logoUrl = computed(() => {
    const s = (page.props.settings ?? {}) as Record<string, any>;
    const path = s.footer_logo_path || s.logo_path;
    return path ? `/storage/${path}` : '/icons/icon-192.png?v=1';
});

// --- detección de dispositivo ---

const isIos = computed(() => /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.userAgent.includes('Mac') && navigator.maxTouchPoints > 1));

const isStandalone = computed(
    () => window.matchMedia('(display-mode: standalone)').matches || (navigator as any).standalone === true
);

// --- estado de instalabilidad ---

const STORAGE_KEY = 'pwa-banner-dismissed';

const canInstall = ref(false);    // Chrome/Edge: el prompt está disponible
const showIosGuide = ref(false);   // iOS: mostrar instrucciones manuales
const dismissed = ref(false);

function refresh() {
    if (dismissed.value || isStandalone.value) return;

    if (isIos.value) {
        // iOS Safari nunca dispara beforeinstallprompt; mostramos siempre el guía.
        showIosGuide.value = true;
    } else {
        canInstall.value = Boolean(window.__pwaInstall?.prompt);
    }
}

function onInstallable() { refresh(); }
function onInstalled() { canInstall.value = false; showIosGuide.value = false; }

onMounted(() => {
    try {
        if (localStorage.getItem(STORAGE_KEY) === '1') {
            dismissed.value = true;
            return;
        }
    } catch {}
    refresh();
    window.addEventListener('pwa:installable', onInstallable);
    window.addEventListener('pwa:installed', onInstalled);
});

onUnmounted(() => {
    window.removeEventListener('pwa:installable', onInstallable);
    window.removeEventListener('pwa:installed', onInstalled);
});

const visible = computed(() => !dismissed.value && !isStandalone.value && (canInstall.value || showIosGuide.value));

async function install() {
    const p = window.__pwaInstall?.prompt;
    if (!p) {
        // El prompt ya se consumió o no existe: mostrar guía de menú del navegador.
        canInstall.value = false;
        showIosGuide.value = true;
        return;
    }
    await p.prompt();
    // El prompt se consume: después del click ya no sirve, lo dejamos visible
    // por si el usuario rechazó y quiere volver a intentar — ahí va al guía manual.
    window.__pwaInstall.prompt = null;
    canInstall.value = false;
    showIosGuide.value = true;
}

// Cierra el banner solo por esta sesión (el ref se resetea al recargar).
function dismiss() {
    dismissed.value = true;
}

// Cierra el banner de forma permanente guardando la preferencia en localStorage.
function dismissForever() {
    try { localStorage.setItem(STORAGE_KEY, '1'); } catch {}
    dismissed.value = true;
}
</script>

<template>
    <!--
        md:hidden: el banner solo se ve en móvil (< 768px).
        En desktop el PWA sigue siendo instalable desde el navegador,
        pero no mostramos UI nuestra para no saturar la interfaz.
    -->
    <div
        v-if="visible"
        class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white px-4 pb-4 pt-3 shadow-lg md:hidden"
        role="banner"
    >
        <div class="flex items-start gap-3">
            <img :src="logoUrl" alt="" class="mt-0.5 h-10 w-10 shrink-0 rounded-xl object-cover" aria-hidden="true" />

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-gray-900">Meditar en Zona Norte</p>

                <!-- Chrome / Edge Android -->
                <template v-if="canInstall && !showIosGuide">
                    <p class="mt-0.5 text-xs text-gray-500">Instalá la app para acceder rápido</p>
                    <div class="mt-2 flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-4 py-1.5 text-xs font-medium text-white transition hover:bg-gray-700 active:scale-95"
                            @click="install"
                        >
                            <Download class="h-3.5 w-3.5" />
                            Instalar
                        </button>
                        <button
                            type="button"
                            class="text-xs text-gray-400 underline-offset-2 hover:underline"
                            @click="dismissForever"
                        >
                            No mostrar más
                        </button>
                    </div>
                </template>

                <!-- iOS Safari o después de consumir el prompt -->
                <template v-else-if="showIosGuide">
                    <p class="mt-0.5 text-xs text-gray-500">
                        Tocá
                        <svg class="mx-0.5 inline h-4 w-4 align-text-bottom" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M8 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-4" />
                            <polyline points="16 6 12 2 8 6" />
                            <line x1="12" y1="2" x2="12" y2="15" />
                        </svg>
                        y luego <strong>«Agregar a inicio»</strong>
                    </p>
                    <button
                        type="button"
                        class="mt-1.5 text-xs text-gray-400 underline-offset-2 hover:underline"
                        @click="dismissForever"
                    >
                        No mostrar más
                    </button>
                </template>
            </div>

            <button
                type="button"
                class="shrink-0 rounded-full p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                aria-label="Cerrar"
                @click="dismiss"
            >
                <X class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
