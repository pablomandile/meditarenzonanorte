<script setup lang="ts">
/** El encabezado centrado de las secciones: línea superior, título, raya dorada y bajada. */
defineProps<{ kicker?: string | null; heading?: string | null; description?: string | null }>();
</script>

<template>
    <div v-if="kicker || heading || description" class="lp-section-heading">
        <p v-if="kicker" class="lp-kicker">{{ kicker }}</p>
        <h2 v-if="heading">{{ heading }}</h2>
        <div class="lp-heading-line"></div>
        <p v-if="description" class="lp-heading-description">{{ description }}</p>
    </div>
</template>
