<script setup lang="ts">
import CardsField from '@/components/admin/fields/CardsField.vue';
import FaqPickerField from '@/components/admin/fields/FaqPickerField.vue';
import ImageField from '@/components/admin/fields/ImageField.vue';
import ImagesField from '@/components/admin/fields/ImagesField.vue';
import ItemsField from '@/components/admin/fields/ItemsField.vue';
import LinksField from '@/components/admin/fields/LinksField.vue';
import PlansField from '@/components/admin/fields/PlansField.vue';
import SelectField from '@/components/admin/fields/SelectField.vue';
import TextareaField from '@/components/admin/fields/TextareaField.vue';
import TextField from '@/components/admin/fields/TextField.vue';

export type FieldDef = {
    key: string;
    type: string;
    label: string;
    options?: Record<string, string>;
};

defineProps<{
    fields: FieldDef[];
    errors: Record<string, string>;
    faqPool?: { id: number; question: string; visible: boolean }[];
}>();

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
                :error="errors[`content.${field.key}`]"
            />

            <TextareaField
                v-else-if="field.type === 'textarea'"
                v-model="content[field.key]"
                :label="field.label"
                :error="errors[`content.${field.key}`]"
            />

            <SelectField
                v-else-if="field.type === 'select'"
                v-model="content[field.key]"
                :label="field.label"
                :options="field.options ?? {}"
                :error="errors[`content.${field.key}`]"
            />

            <ImageField
                v-else-if="field.type === 'image'"
                v-model="content[field.key]"
                v-model:file="files[field.key]"
                :label="field.label"
                gallery
                :error="errors[`content.${field.key}`] ?? errors[`files.${field.key}`]"
            />

            <LinksField
                v-else-if="field.type === 'links'"
                v-model="content[field.key]"
                :label="field.label"
                :error="errors[`content.${field.key}`]"
            />

            <ItemsField
                v-else-if="field.type === 'items'"
                v-model="content[field.key]"
                :label="field.label"
                :error="errors[`content.${field.key}`]"
            />

            <PlansField
                v-else-if="field.type === 'plans'"
                v-model="content[field.key]"
                :label="field.label"
                :error="errors[`content.${field.key}`]"
            />

            <CardsField
                v-else-if="field.type === 'cards'"
                v-model="content[field.key]"
                v-model:files="files[field.key]"
                :label="field.label"
                :error="errors[`content.${field.key}`]"
            />

            <ImagesField
                v-else-if="field.type === 'images'"
                v-model="content[field.key]"
                v-model:files="files[field.key]"
                :label="field.label"
                :error="errors[`content.${field.key}`]"
            />

            <FaqPickerField
                v-else-if="field.type === 'faq_picker'"
                v-model="content[field.key]"
                :label="field.label"
                :pool="faqPool ?? []"
                :error="errors[`content.${field.key}`]"
            />
        </template>
    </div>
</template>
