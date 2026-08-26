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
        <!--
            Medido: con la tipografía holgada la barra necesita ~1351px (logo 202 +
            nav 925 + teléfono 144 + espacios). Entre xl (1280) y eso, el nav entraba
            pero envolvía cada etiqueta en dos renglones. Por eso hay un escalón
            compacto para ese tramo y el holgado vuelve recién en min-[1400px].
        -->
        <div class="mx-auto flex h-20 items-center justify-between gap-3 px-4 lg:px-6 min-[1400px]:gap-4">
            <!--
                El logo se mide contra el alto de la barra: h-full en el enlace (el
                contenedor tiene alto fijo) y un porcentaje en la imagen, así ocupa
                casi todo el alto dejando un aire mínimo, y el ancho lo define su
                propia proporción sin deformarse. En el tramo compacto baja al 75%,
                que en un logo apaisado son unos 35px menos de ancho.
            -->
            <Link href="/" class="flex h-full shrink-0 items-center gap-3" aria-label="Inicio">
                <img
                    v-if="settings.logo_path"
                    :src="img(settings.logo_path)"
                    alt="Logo"
                    class="h-[75%] w-auto object-contain min-[1400px]:h-[90%]"
                />
                <span v-else class="font-display text-xl text-brand-ink">{{ settings.site_name }}</span>
            </Link>

            <!--
                whitespace-nowrap es lo que evita el renglón doble: sin él, al faltar
                lugar cada etiqueta se parte por su espacio ("Clases / semanales").
            -->
            <nav class="hidden items-center gap-4 xl:flex min-[1400px]:gap-6">
                <Link
                    v-for="item in nav"
                    :key="item.slug"
                    :href="`/${item.slug}`"
                    class="whitespace-nowrap text-sm font-medium text-brand-ink transition hover:text-brand-sky min-[1400px]:text-[15px]"
                    :class="{ 'text-brand-sky': currentUrl.startsWith(`/${item.slug}`) }"
                >
                    {{ item.label }}
                </Link>
            </nav>

            <div class="flex items-center gap-3">
                <a
                    v-if="settings.phone_display"
                    :href="settings.phone_link ?? undefined"
                    class="hidden shrink-0 items-center gap-2 whitespace-nowrap rounded-full bg-brand-sky px-3 py-2 text-xs font-medium text-white transition hover:bg-brand-sky-dark sm:flex min-[1400px]:px-4 min-[1400px]:text-sm"
                >
                    <Phone class="h-4 w-4 shrink-0" />
                    {{ settings.phone_display }}
                </a>

                <button
                    type="button"
                    class="rounded-md p-2 text-brand-ink hover:bg-brand-light xl:hidden"
                    :aria-expanded="open"
                    aria-label="Abrir menú"
                    @click="open = !open"
                >
                    <X v-if="open" class="h-6 w-6" />
                    <Menu v-else class="h-6 w-6" />
                </button>
            </div>
        </div>

        <nav v-if="open" class="border-t border-brand-line/60 bg-white px-4 pb-4 xl:hidden">
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
