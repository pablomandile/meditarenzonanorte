<script setup lang="ts">
import { type SectionData } from '@/lib/site';
import { computed } from 'vue';

const props = defineProps<{ section: SectionData }>();
const c = computed(() => props.section.content);
</script>

<template>
    <section v-if="c.quote" class="lp-quote-section">
        <div class="lp-quote-mark" aria-hidden="true">“</div>
        <blockquote>{{ c.quote }}</blockquote>
        <p v-if="c.author" class="lp-quote-credit">— {{ c.author }}</p>
    </section>
</template>
