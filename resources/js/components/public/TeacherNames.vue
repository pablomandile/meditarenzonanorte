<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { img, paragraphs } from '@/lib/site';
import { computed, ref } from 'vue';

export type TeacherProfile = { name: string; description: string | null; photo: string | null };

/**
 * "Ana, Luis" → "Ana y Luis", con cada nombre que tiene descripción o foto en
 * "Datos recurrentes" como botón que abre una ventanita con eso. Los perfiles
 * llegan del servidor por nombre en minúsculas (PageController::teacherProfiles).
 */
const props = defineProps<{ names?: string | null; profiles?: Record<string, TeacherProfile> | null }>();

const list = computed(() =>
    (props.names ?? '')
        .split(',')
        .map((name) => name.trim())
        .filter(Boolean)
        .map((name) => ({ name, profile: props.profiles?.[name.toLowerCase()] ?? null })),
);

const open = ref<TeacherProfile | null>(null);

function separator(index: number): string {
    const count = list.value.length;

    if (index === count - 1) return '';

    return index === count - 2 ? ' y ' : ', ';
}
</script>

<template>
    <!--
        Todo pegado a propósito: la ficha usa whitespace-pre-line en el título, y un
        salto de línea o un espacio entre etiquetas se vería.
    -->
    <template v-for="(item, index) in list" :key="item.name"
        ><button
            v-if="item.profile"
            type="button"
            class="cursor-pointer underline decoration-dotted decoration-1 underline-offset-4 hover:decoration-solid focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-orange/50"
            :title="`Conocé a ${item.profile.name}`"
            @click="open = item.profile"
        >
            {{ item.name }}</button
        ><template v-else>{{ item.name }}</template
        >{{ separator(index) }}</template
    >

    <Dialog :open="!!open" @update:open="(value) => !value && (open = null)">
        <DialogContent v-if="open" class="max-h-[85vh] max-w-md overflow-y-auto border-brand-line bg-white p-6 text-brand-body">
            <img
                v-if="open.photo"
                :src="img(open.photo)"
                :alt="open.name"
                class="mx-auto aspect-square w-40 rounded-full object-cover shadow-md ring-1 ring-brand-line/50"
            />
            <DialogHeader class="text-center">
                <DialogTitle class="font-heading text-2xl font-normal text-brand-ink">{{ open.name }}</DialogTitle>
                <DialogDescription class="sr-only">Quién es {{ open.name }}</DialogDescription>
            </DialogHeader>
            <p v-for="(p, i) in paragraphs(open.description)" :key="i" class="leading-relaxed text-brand-body">{{ p }}</p>
        </DialogContent>
    </Dialog>
</template>
