<script setup lang="ts">
import ImageField from '@/components/admin/fields/ImageField.vue';
import TextareaField from '@/components/admin/fields/TextareaField.vue';
import TextField from '@/components/admin/fields/TextField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { watch } from 'vue';

export type TeacherItem = { id: number; name: string; description: string | null; photo: string | null };

/**
 * La ficha de un maestr@ de "Datos recurrentes": nombre, descripción y foto. Lo
 * que se carga acá se ve al tocar su nombre en una ficha de clase y en las
 * secciones "Maestro / persona" que lo eligen.
 */
const props = defineProps<{ teacher: TeacherItem | null }>();
const emit = defineEmits<{ close: [] }>();

const form = useForm<{ _method: string; name: string; description: string | null; photo: string | null; file: File | null }>({
    _method: 'put',
    name: '',
    description: null,
    photo: null,
    file: null,
});

watch(
    () => props.teacher,
    (teacher) => {
        if (!teacher) return;
        form.clearErrors();
        form.defaults({ _method: 'put', name: teacher.name, description: teacher.description, photo: teacher.photo, file: null });
        form.reset();
    },
    { immediate: true },
);

function save() {
    if (!props.teacher) return;

    form.post(route('admin.recurring.teachers.update', props.teacher.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Dialog :open="!!teacher" @update:open="(open) => !open && emit('close')">
        <DialogContent class="max-h-[90vh] max-w-lg overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Editar maestr@</DialogTitle>
                <DialogDescription>
                    La descripción y la foto se ven al tocar su nombre en una ficha de clase, y en ¿Quiénes somos? si una sección lo elige.
                </DialogDescription>
            </DialogHeader>

            <form id="teacher-form" class="grid gap-5" @submit.prevent="save">
                <TextField
                    v-model="form.name"
                    label="Nombre"
                    :error="form.errors.name"
                    hint="Si lo cambiás, se cambia también en las fichas que lo nombran."
                />
                <TextareaField v-model="form.description" label="Descripción" :rows="8" :error="form.errors.description" />
                <ImageField v-model="form.photo" v-model:file="form.file" label="Foto" gallery :error="form.errors.file || form.errors.photo" />
            </form>

            <DialogFooter>
                <Button variant="ghost" type="button" @click="emit('close')">Cancelar</Button>
                <Button type="submit" form="teacher-form" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                    Guardar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
