<script setup lang="ts">
import CalendarActivity from '@/components/public/CalendarActivity.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DIAS, sourceStyles, styleFor, timeLabel, type CalendarData } from '@/lib/calendar';
import { paragraphs, type SectionData } from '@/lib/site';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ section: SectionData; calendar?: CalendarData }>();

const selectedDate = ref<string | null>(null);
const weekIndex = ref(0);

const weeks = computed(() => props.calendar?.weeks ?? []);
const week = computed(() => weeks.value[weekIndex.value] ?? null);
const styles = computed(() => sourceStyles(props.calendar?.sources ?? []));
const days = computed(() => weeks.value.flatMap((w) => w.days).filter((day) => day !== null));
const hasActivities = computed(() => days.value.some((day) => day.activities.length > 0));
const selectedDay = computed(() => days.value.find((day) => day.date === selectedDate.value) ?? null);

/** En celular se muestra una semana por vez, dentro del mes. */
const weekDays = computed(() => (week.value?.days ?? []).filter((day) => day !== null));
const weekHasToday = computed(() => weekDays.value.some((day) => day.is_today));

function stepWeek(direction: -1 | 1) {
    const next = weekIndex.value + direction;

    if (next >= 0 && next < weeks.value.length) weekIndex.value = next;
}

/** Arranca en la semana de hoy, que es la que la persona vino a ver. */
function pickTodaysWeek() {
    const found = weeks.value.findIndex((w) => w.days.some((day) => day?.is_today));

    weekIndex.value = found >= 0 ? found : 0;
}

watch(() => props.calendar?.month, pickTodaysWeek, { immediate: true });

function dayAriaLabel(label: string, count: number): string {
    if (!count) return label;

    return `${label}: ${count === 1 ? '1 actividad' : `${count} actividades`}`;
}
</script>

<template>
    <section v-if="calendar" class="py-8 md:py-12">
        <div class="mx-auto max-w-6xl px-4">
            <h2 v-if="section.content.heading" class="text-center font-heading text-3xl font-light text-brand-sky md:text-[35px]">
                {{ section.content.heading }}
            </h2>

            <p
                v-for="(p, i) in paragraphs(section.content.intro)"
                :key="i"
                class="mx-auto mt-3 max-w-3xl text-center leading-relaxed text-brand-body"
            >
                {{ p }}
            </p>

            <h3 class="mb-5 mt-8 hidden text-center font-heading text-2xl font-light leading-none text-brand-sky first-letter:uppercase md:block md:text-[28px]">
                {{ calendar.label }}
            </h3>

            <!-- Escritorio: la grilla del mes. Es una tabla de verdad (día × semana). -->
            <div class="hidden md:block">
                <div class="overflow-hidden rounded-xl">
                    <table class="w-full table-fixed border-collapse">
                        <caption class="sr-only">Actividades de {{ calendar.label }}</caption>
                        <thead>
                            <tr>
                                <th
                                    v-for="dia in DIAS"
                                    :key="dia.long"
                                    scope="col"
                                    class="border border-brand-line/50 bg-brand-light/60 px-1 py-2 text-center text-xs font-semibold uppercase tracking-wide text-brand-sky-dark"
                                >
                                    <span aria-hidden="true">{{ dia.short }}</span>
                                    <span class="sr-only">{{ dia.long }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, w) in weeks" :key="w">
                                <template v-for="(day, d) in row.days" :key="d">
                                    <!-- Fuera del mes no se dibuja nada, ni el número. -->
                                    <td v-if="!day" class="border border-brand-line/50 bg-brand-line/10 p-0"></td>

                                    <td
                                        v-else
                                        class="border border-brand-line/50 bg-white p-0 align-top"
                                        :aria-current="day.is_today ? 'date' : undefined"
                                    >
                                        <component
                                            :is="day.activities.length ? 'button' : 'div'"
                                            v-bind="
                                                day.activities.length
                                                    ? {
                                                          type: 'button',
                                                          'aria-haspopup': 'dialog',
                                                          'aria-label': dayAriaLabel(day.label, day.activities.length),
                                                      }
                                                    : {}
                                            "
                                            class="flex min-h-[104px] w-full flex-col items-start gap-1 p-1.5 text-left lg:min-h-[120px]"
                                            :class="
                                                day.activities.length
                                                    ? 'cursor-pointer transition hover:bg-brand-light/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-sky'
                                                    : ''
                                            "
                                            @click="day.activities.length && (selectedDate = day.date)"
                                        >
                                            <span
                                                v-if="day.is_today"
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-sky text-[13px] font-semibold leading-none text-white"
                                            >
                                                {{ day.day }}
                                            </span>
                                            <span v-else class="px-0.5 text-[13px] font-semibold leading-none text-brand-ink">{{ day.day }}</span>

                                            <span
                                                v-for="activity in day.activities.slice(0, 2)"
                                                :key="activity.key"
                                                class="flex w-full min-w-0 items-center gap-1 rounded px-1 py-0.5 text-[11px] leading-tight ring-1"
                                                :class="styleFor(styles, activity.source.slug).chip"
                                            >
                                                <component
                                                    :is="styleFor(styles, activity.source.slug).icon"
                                                    class="h-3 w-3 shrink-0"
                                                    :class="styleFor(styles, activity.source.slug).iconText"
                                                    aria-hidden="true"
                                                />
                                                <span class="sr-only">{{ activity.source.title }}:</span>
                                                <span v-if="activity.start" class="shrink-0 font-semibold tabular-nums">
                                                    {{ timeLabel(activity.start) }} hs
                                                </span>
                                                <span class="truncate">{{ activity.title }}</span>
                                            </span>

                                            <span v-if="day.activities.length > 2" class="pl-1 text-[11px] font-medium text-brand-sky">
                                                +{{ day.activities.length - 2 }} más
                                            </span>
                                        </component>
                                    </td>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Celular: una semana por vez, con las actividades desplegadas. -->
            <div class="md:hidden">
                <div class="mb-3 mt-6 flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="weekIndex === 0"
                        aria-label="Semana anterior"
                        title="Semana anterior"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-brand-sky ring-1 ring-brand-line transition hover:bg-brand-light disabled:opacity-30"
                        @click="stepWeek(-1)"
                    >
                        <ChevronLeft class="h-5 w-5" />
                    </button>

                    <div class="min-w-0 flex-1 px-1 text-center">
                        <p class="truncate font-heading text-base font-medium leading-tight text-brand-sky first-letter:uppercase">
                            {{ week?.label }}
                        </p>
                        <p class="truncate text-[11px] uppercase tracking-wide text-brand-muted">{{ calendar.label }}</p>
                    </div>

                    <button
                        type="button"
                        :disabled="weekIndex >= weeks.length - 1"
                        aria-label="Semana siguiente"
                        title="Semana siguiente"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-brand-sky ring-1 ring-brand-line transition hover:bg-brand-light disabled:opacity-30"
                        @click="stepWeek(1)"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>

                <button
                    v-if="!weekHasToday"
                    type="button"
                    class="mx-auto mb-3 block rounded-full px-3 py-1 text-xs font-medium uppercase tracking-wide text-brand-sky ring-1 ring-brand-sky/40"
                    @click="pickTodaysWeek"
                >
                    volver a hoy
                </button>

                <ol class="space-y-2">
                    <li
                        v-for="day in weekDays"
                        :key="day.date"
                        class="overflow-hidden rounded-xl"
                        :class="day.is_today ? 'ring-2 ring-brand-sky' : 'ring-1 ring-brand-line/60'"
                    >
                        <div class="flex items-baseline gap-2 bg-brand-light/60 px-3 py-2">
                            <span class="text-[11px] font-semibold uppercase tracking-wide text-brand-sky-dark">
                                {{ DIAS[day.weekday - 1].short }}
                            </span>
                            <span class="font-heading text-lg font-semibold leading-none text-brand-ink">{{ day.day }}</span>
                            <span
                                v-if="day.is_today"
                                class="ml-auto rounded-full bg-brand-sky px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white"
                            >
                                hoy
                            </span>
                        </div>

                        <p v-if="!day.activities.length" class="px-3 py-2 text-xs italic text-brand-muted">Sin actividades</p>

                        <ul v-else class="divide-y divide-brand-line/40">
                            <li
                                v-for="activity in day.activities"
                                :key="activity.key"
                                class="border-l-4 px-3 py-2.5"
                                :class="styleFor(styles, activity.source.slug).bar"
                            >
                                <CalendarActivity :activity="activity" :style="styleFor(styles, activity.source.slug)" />
                            </li>
                        </ul>
                    </li>
                </ol>
            </div>

            <p v-if="!hasActivities" class="mt-5 text-center font-display text-xl uppercase tracking-wide text-brand-muted">
                {{ section.content.empty_text ?? 'este mes no tiene actividades cargadas' }}
            </p>

            <h3 class="sr-only">Referencias del calendario</h3>
            <ul
                v-if="calendar.sources.length"
                class="mt-4 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs md:mt-5"
                aria-label="Cada color y símbolo identifica la sección del sitio de donde sale la actividad"
            >
                <li v-for="source in calendar.sources" :key="source.slug" class="flex items-center gap-1.5">
                    <span class="flex h-4 w-4 items-center justify-center rounded-full" :class="styleFor(styles, source.slug).dot" aria-hidden="true">
                        <component :is="styleFor(styles, source.slug).icon" class="h-2.5 w-2.5 text-white" />
                    </span>
                    <Link
                        v-if="source.url"
                        :href="source.url"
                        class="text-brand-body underline decoration-brand-line underline-offset-2 transition hover:text-brand-sky hover:decoration-brand-sky"
                    >
                        {{ source.title }}
                    </Link>
                    <span v-else class="text-brand-body">{{ source.title }}</span>
                </li>
            </ul>
        </div>

        <!--
            El detalle del día es sólo de escritorio: en celular la semana ya muestra
            todo. Una píldora de 105px no puede contener el lugar, el precio ni el
            botón, y el diálogo de radix trae foco atrapado, Esc y restauración.
        -->
        <Dialog :open="!!selectedDay" @update:open="(open) => !open && (selectedDate = null)">
            <DialogContent v-if="selectedDay" class="max-h-[85vh] max-w-lg overflow-y-auto border-brand-line bg-white p-5 text-brand-body">
                <DialogHeader class="text-left">
                    <DialogTitle class="pr-8 font-heading text-xl font-normal text-brand-sky first-letter:uppercase">
                        {{ selectedDay.label }}
                    </DialogTitle>
                    <DialogDescription class="text-sm text-brand-muted">
                        {{ selectedDay.activities.length === 1 ? '1 actividad' : `${selectedDay.activities.length} actividades` }}
                    </DialogDescription>
                </DialogHeader>

                <ul v-if="selectedDay.activities.length" class="divide-y divide-brand-line/50">
                    <li
                        v-for="activity in selectedDay.activities"
                        :key="activity.key"
                        class="border-l-4 py-3 pl-3"
                        :class="styleFor(styles, activity.source.slug).bar"
                    >
                        <CalendarActivity :activity="activity" :style="styleFor(styles, activity.source.slug)" />
                    </li>
                </ul>
                <p v-else class="text-sm italic text-brand-muted">No hay actividades este día.</p>
            </DialogContent>
        </Dialog>
    </section>
</template>
