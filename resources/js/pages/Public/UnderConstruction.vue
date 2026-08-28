<script setup lang="ts">
import ConstructionArt from '@/components/public/ConstructionArt.vue';
import { img, instagramUrl } from '@/lib/site';
import { Head, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Construction, Instagram, Mail, MapPin, Phone } from 'lucide-vue-next';
import { computed } from 'vue';

/**
 * El cartel que ven las visitas mientras el sitio está cerrado desde Ajustes.
 *
 * No usa PublicLayout a propósito: el menú llevaría a páginas que el middleware
 * también tapa, así que la única salida que se ofrece es el contacto. Ver
 * App\Http\Middleware\UnderConstruction.
 *
 * preview: lo pone la vista del panel, que muestra el cartel sin cerrar el sitio.
 */
defineProps<{ title: string; message: string; preview?: boolean }>();

const page = usePage();
const settings = computed(() => (page.props.settings ?? {}) as Record<string, any>);
const siteName = computed(() => (page.props.name ?? '') as string);
const year = new Date().getFullYear();
</script>

<template>
    <Head :title="title">
        <!-- Refuerza el 503 del middleware para el buscador que igual llegue a mirar. -->
        <meta name="robots" content="noindex, follow" />
    </Head>

    <div
        class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-white px-5 py-16 font-sans text-brand-body antialiased"
    >
        <!-- Manchas de color desenfocadas: dan profundidad sin cargar ninguna imagen. -->
        <div class="pointer-events-none absolute -left-40 -top-48 h-[30rem] w-[30rem] rounded-full bg-brand-light opacity-70 blur-3xl" />
        <div class="pointer-events-none absolute -bottom-52 -right-40 h-[34rem] w-[34rem] rounded-full bg-brand-cream opacity-80 blur-3xl" />

        <div class="relative w-full max-w-2xl text-center">
            <img v-if="settings.logo_path" :src="img(settings.logo_path)" :alt="siteName" class="mx-auto h-14 w-auto object-contain" />
            <p v-else-if="siteName" class="font-display text-2xl tracking-wide text-brand-ink">{{ siteName }}</p>

            <ConstructionArt class="mx-auto mt-4 w-full max-w-[22rem] sm:max-w-[26rem]" />

            <span
                class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-cream px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-brand-orange-dark"
            >
                <Construction class="h-4 w-4" />
                En construcción
            </span>

            <h1 class="mt-5 font-heading text-3xl font-light leading-tight text-brand-ink sm:text-[2.75rem]">
                {{ title }}
            </h1>

            <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-brand-body sm:text-lg">
                {{ message }}
            </p>

            <!-- La única salida del cartel: el contacto. Cada vía aparece sólo si está cargada en Ajustes. -->
            <div class="mt-9 flex justify-center">
                <a
                    v-if="settings.whatsapp_url"
                    :href="settings.whatsapp_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 rounded-full bg-[#25D366] px-6 py-3 text-sm font-medium text-white shadow-sm transition hover:brightness-95"
                >
                    <svg viewBox="0 0 32 32" class="h-5 w-5 fill-current" aria-hidden="true">
                        <path
                            d="M16.004 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.257.59 4.462 1.71 6.402L3.2 28.8l6.58-1.672a12.73 12.73 0 0 0 6.222 1.606h.006c7.058 0 12.798-5.74 12.798-12.8s-5.74-12.734-12.802-12.734zm0 23.398h-.005a10.6 10.6 0 0 1-5.4-1.48l-.387-.23-3.905.993 1.043-3.807-.252-.39a10.55 10.55 0 0 1-1.628-5.684c0-5.86 4.772-10.628 10.639-10.628 2.84 0 5.51 1.107 7.517 3.117a10.56 10.56 0 0 1 3.112 7.52c-.003 5.86-4.775 10.59-10.734 10.59zm5.834-7.955c-.32-.16-1.888-.932-2.18-1.038-.293-.107-.506-.16-.72.16-.213.32-.825 1.037-1.011 1.25-.187.214-.373.24-.693.08-.32-.16-1.348-.497-2.568-1.584-.95-.847-1.59-1.894-1.777-2.214-.187-.32-.02-.493.14-.652.144-.144.32-.374.48-.56.16-.187.213-.32.32-.534.107-.213.053-.4-.027-.56-.08-.16-.719-1.734-.985-2.374-.26-.624-.523-.54-.72-.55l-.612-.01c-.213 0-.56.08-.853.4-.293.32-1.119 1.094-1.119 2.667s1.146 3.094 1.306 3.307c.16.214 2.255 3.444 5.464 4.83.764.33 1.36.526 1.824.673.767.244 1.464.21 2.015.127.615-.092 1.888-.772 2.155-1.518.266-.746.266-1.386.186-1.519-.08-.133-.293-.213-.613-.373z"
                        />
                    </svg>
                    Escribinos por WhatsApp
                </a>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-center gap-3">
                <a
                    v-if="settings.phone_display"
                    :href="settings.phone_link ?? undefined"
                    class="inline-flex items-center gap-2 rounded-full border border-brand-line bg-white px-5 py-3 text-sm font-medium text-brand-ink transition hover:border-brand-sky hover:text-brand-sky"
                >
                    <Phone class="h-4 w-4" />
                    {{ settings.phone_display }}
                </a>

                <a
                    v-if="settings.email"
                    :href="`mailto:${settings.email}`"
                    class="inline-flex items-center gap-2 rounded-full border border-brand-line bg-white px-5 py-3 text-sm font-medium text-brand-ink transition hover:border-brand-sky hover:text-brand-sky"
                >
                    <Mail class="h-4 w-4" />
                    {{ settings.email }}
                </a>

                <a
                    v-if="settings.instagram_url"
                    :href="instagramUrl(settings.instagram_url)"
                    target="_blank"
                    rel="noopener"
                    aria-label="Instagram"
                    class="inline-flex items-center gap-2 rounded-full border border-brand-line bg-white p-3 text-brand-ink transition hover:border-brand-sky hover:text-brand-sky"
                >
                    <Instagram class="h-4 w-4" />
                </a>
            </div>

            <!-- items-start: partida en dos renglones, el pin tiene que quedar contra el primero. -->
            <p v-if="settings.address" class="mx-auto mt-6 flex max-w-sm items-start justify-center gap-2 text-sm text-brand-muted">
                <MapPin class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ settings.address }}</span>
            </p>

            <p class="mt-12 text-xs text-brand-muted">© {{ year }} {{ siteName }}</p>
        </div>

        <!-- Sólo en la vista del panel: recuerda que esto es una prueba y no el sitio caído. -->
        <a
            v-if="preview"
            :href="route('admin.settings.edit')"
            class="fixed left-1/2 top-4 z-50 inline-flex -translate-x-1/2 items-center gap-2 rounded-full bg-brand-ink/90 px-4 py-2 text-xs font-medium text-white shadow-lg backdrop-blur transition hover:bg-brand-ink"
        >
            <ArrowLeft class="h-4 w-4" />
            Vista previa — volver a Ajustes
        </a>
    </div>
</template>
