<script setup lang="ts">
import CardsField from '@/components/admin/fields/CardsField.vue';
import FaqPickerField from '@/components/admin/fields/FaqPickerField.vue';
import ImageField from '@/components/admin/fields/ImageField.vue';
import ImagesField from '@/components/admin/fields/ImagesField.vue';
import ItemsField from '@/components/admin/fields/ItemsField.vue';
import LinksField from '@/components/admin/fields/LinksField.vue';
import OccurrencesField from '@/components/admin/fields/OccurrencesField.vue';
import PlansField from '@/components/admin/fields/PlansField.vue';
import ReviewsField from '@/components/admin/fields/ReviewsField.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import SuggestField from '@/components/admin/fields/SuggestField.vue';
import TagsField from '@/components/admin/fields/TagsField.vue';
import TextareaField from '@/components/admin/fields/TextareaField.vue';
import TextField from '@/components/admin/fields/TextField.vue';

export type FieldDef = {
    key: string;
    type: string;
    label: string;
    options?: Record<string, string>;
    /** Para 'tags' y 'suggest': qué lista de "Datos recurrentes" ofrecer. */
    pool?: string;
};

const props = defineProps<{
    fields: FieldDef[];
    errors: Record<string, string>;
    faqPool?: { id: number; question: string; visible: boolean }[];
    /** Listas de "Datos recurrentes" por nombre (teachers, venues). */
    pools?: Record<string, string[]>;
    /** Aclaraciones por clave de campo, que el servidor calcula (ej. el horario). */
    hints?: Record<string, string>;
}>();

/**
 * El error del campo. Los repetidores validan cada fila por separado
 * ("content.occurrences.1.weekday"), y esa clave no coincide con la del campo:
 * sin este respaldo el guardado fallaría sin mostrar ningún mensaje.
 */
function fieldError(key: string): string | undefined {
    const own = props.errors[`content.${key}`] ?? props.errors[`files.${key}`];

    if (own) return own;

    const nested = Object.keys(props.errors).find((error) => error.startsWith(`content.${key}.`) || error.startsWith(`files.${key}.`));

    return nested ? props.errors[nested] : undefined;
}

// content y files son contenedores reactivos que llenan los campos hijos (two-way).
const content = defineModel<Record<string, any>>('content', { required: true });
const files = defineModel<Record<string, any>>('files', { required: true });
</script>

<template>
    <div class="grid gap-6">
        <template v-for="field in fields" :key="field.key">
            <TextField
                v-if="field.type === 'text' || field.type === 'url'"
                v-model="content[field.key]"
                :label="field.label"
                :hint="hints?.[field.key]"
                :error="fieldError(field.key)"
            />

            <TagsField
                v-else-if="field.type === 'tags'"
                v-model="content[field.key]"
                :label="field.label"
                :options="pools?.[field.pool ?? ''] ?? []"
                :error="fieldError(field.key)"
            />

            <SuggestField
                v-else-if="field.type === 'suggest'"
                v-model="content[field.key]"
                :label="field.label"
                :options="pools?.[field.pool ?? ''] ?? []"
                :hint="hints?.[field.key]"
                :error="fieldError(field.key)"
            />

            <TextareaField v-else-if="field.type === 'textarea'" v-model="content[field.key]" :label="field.label" :error="fieldError(field.key)" />

            <SelectField
                v-else-if="field.type === 'select'"
                v-model="content[field.key]"
                :label="field.label"
                :options="field.options ?? {}"
                :error="fieldError(field.key)"
            />

            <ImageField
                v-else-if="field.type === 'image'"
                v-model="content[field.key]"
                v-model:file="files[field.key]"
                :label="field.label"
                gallery
                :error="fieldError(field.key)"
            />

            <LinksField v-else-if="field.type === 'links'" v-model="content[field.key]" :label="field.label" :error="fieldError(field.key)" />

            <ItemsField v-else-if="field.type === 'items'" v-model="content[field.key]" :label="field.label" :error="fieldError(field.key)" />

            <PlansField v-else-if="field.type === 'plans'" v-model="content[field.key]" :label="field.label" :error="fieldError(field.key)" />

            <ReviewsField v-else-if="field.type === 'reviews'" v-model="content[field.key]" :label="field.label" :error="fieldError(field.key)" />

            <CardsField
                v-else-if="field.type === 'cards'"
                v-model="content[field.key]"
                v-model:files="files[field.key]"
                :label="field.label"
                :error="fieldError(field.key)"
            />

            <ImagesField
                v-else-if="field.type === 'images'"
                v-model="content[field.key]"
                v-model:files="files[field.key]"
                :label="field.label"
                :error="fieldError(field.key)"
            />

            <OccurrencesField
                v-else-if="field.type === 'occurrences'"
                v-model="content[field.key]"
                :label="field.label"
                :error="fieldError(field.key)"
            />

            <FaqPickerField
                v-else-if="field.type === 'faq_picker'"
                v-model="content[field.key]"
                :label="field.label"
                :pool="faqPool ?? []"
                :error="fieldError(field.key)"
            />
        </template>
    </div>
</template>
