<script setup lang="ts">
import { img, type EventData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';

withDefaults(defineProps<{ event: EventData; featured?: boolean }>(), { featured: false });
</script>

<template>
    <Link href="/eventos-especiales" class="group block overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-brand-line/50">
        <div v-if="event.image_path" class="overflow-hidden">
            <img
                :src="img(event.image_path)"
                :alt="event.title"
                class="aspect-square w-full object-cover transition duration-300 group-hover:scale-[1.03]"
            />
        </div>
        <div :class="featured ? 'p-7' : 'p-5'">
            <h3
                class="font-heading font-semibold leading-snug text-brand-ink group-hover:text-brand-sky"
                :class="featured ? 'text-2xl md:text-3xl' : 'text-lg'"
            >
                {{ event.title }}
            </h3>
            <p
                v-if="event.date_text"
                class="mt-2 flex items-center gap-2 text-brand-muted"
                :class="featured ? 'text-base md:text-lg' : 'text-sm'"
            >
                <Calendar class="text-brand-orange" :class="featured ? 'h-5 w-5' : 'h-4 w-4'" /> {{ event.date_text }}
            </p>
        </div>
    </Link>
</template>
