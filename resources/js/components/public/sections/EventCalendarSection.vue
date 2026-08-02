<script setup lang="ts">
import CalendarActivity from '@/components/public/CalendarActivity.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DIAS, sourceStyles, styleFor, timeLabel, type CalendarData } from '@/lib/calendar';
import { paragraphs, type SectionData } from '@/lib/site';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ section: SectionData; calendar?: CalendarData }>();

const page = usePage();

const loading = ref(false);
const selectedDate = ref<string | null>(null);
const weekIndex = ref(0);
/** Al cruzar de mes hay que recordar de qué semana venimos (ver pickWeek). */
const pending = ref<{ monday: string; dir: 1 | -1 } | null>(null);

const weeks = computed(() => props.calendar?.weeks ?? []);
const week = computed(() => weeks.value[weekIndex.value] ?? null);
const styles = computed(() => sourceStyles(props.calendar?.sources ?? []));
const days = computed(() => weeks.value.flatMap((w) => w.days));
const hasActivities = computed(() => days.value.some((day) => day.activities.length > 0));
const selectedDay = computed(() => days.value.find((day) => day.date === selectedDate.value) ?? null);
const isCurrentMonth = computed(() => props.calendar?.month === props.calendar?.today.slice(0, 7));
const weekHasToday = computed(() => !!week.value?.days.some((day) => day.is_today));

function goToMonth(month?: string | null) {
    if (!month || loading.value) return;

    router.get(
        page.url.split('?')[0],
        { mes: month },
        {
            only: ['calendar'],
            // Sin preserveState el componente se vuelve a montar y se pierden
            // weekIndex y pending, que es justo lo que hace andar el cruce de mes.
            preserveState: true,
            preserveScroll: true,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        },
    );
}

function stepWeek(dir: 1 | -1) {
    if (loading.value) return;

    const next = weekIndex.value + dir;

    if (next >= 0 && next < weeks.value.length) {
        weekIndex.value = next;

        return;
    }

    pending.value = { monday: week.value!.days[0].date, dir };
    goToMonth(dir === 1 ? props.calendar!.next : props.calendar!.prev);
}

/**
 * Qué semana mostrar cuando llegan los datos de un mes. Dos meses seguidos
 * comparten la semana del borde, así que al cruzar no sirve "la primera del mes":
 * puede ser la misma que se estaba viendo. Se busca la primera realmente posterior
 * (o anterior). Las fechas ISO se comparan como texto.
 */
function pickWeek() {
    const list = weeks.value;

    if (!list.length) {
        weekIndex.value = 0;

        return;
    }

    if (pending.value) {
        const { monday, dir } = pending.value;
        const mondays = list.map((w) => w.days[0].date);
        const found = dir === 1 ? mondays.findIndex((d) => d > monday) : mondays.filter((d) => d < monday).length - 1;

        weekIndex.value = found >= 0 ? found : dir === 1 ? 0 : list.length - 1;
        pending.value = null;

        return;
    }

    const today = list.findIndex((w) => w.days.some((day) => day.in_month && day.date === props.calendar!.today));

    weekIndex.value = today >= 0 ? today : 0;
}

function goToToday() {
    pending.value = null;

    if (isCurrentMonth.value) {
        pickWeek();
    } else {
        goToMonth(props.calendar!.today.slice(0, 7));
    }
}

// Al cambiar de mes se cierra el detalle abierto: sería de un día que ya no se ve.
watch(
    () => props.calendar?.month,
    () => {
        selectedDate.value = null;
        pickWeek();
    },
    { immediate: true },
);

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

            <p class="sr-only" role="status" aria-live="polite">
                {{ loading ? 'Cargando el calendario…' : `Mostrando ${calendar.label}` }}
            </p>

            <!-- Navegación por mes: en celular se navega por semana. -->
            <div class="mb-5 mt-8 hidden items-center justify-center gap-3 md:flex">
                <button
                    type="button"
                    :disabled="loading"
                    aria-label="Mes anterior"
                    title="Mes anterior"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-brand-sky ring-1 ring-brand-line transition hover:bg-brand-light disabled:opacity-40"
                    @click="goToMonth(calendar.prev)"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>

                <h3 class="min-w-[13rem] text-center font-heading text-2xl font-light leading-none text-brand-sky first-letter:uppercase md:text-[28px]">
                    {{ calendar.label }}
                </h3>

                <button
                    type="button"
                    :disabled="loading"
                    aria-label="Mes siguiente"
                    title="Mes siguiente"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-brand-sky ring-1 ring-brand-line transition hover:bg-brand-light disabled:opacity-40"
                    @click="goToMonth(calendar.next)"
                >
                    <ChevronRight class="h-5 w-5" />
                </button>

                <button
                    v-if="!isCurrentMonth"
                    type="button"
                    :disabled="loading"
                    class="ml-2 rounded-full px-3 py-1.5 text-xs font-medium uppercase tracking-wide text-brand-sky ring-1 ring-brand-sky/40 transition hover:bg-brand-light disabled:opacity-40"
                    @click="goToToday"
                >
                    hoy
                </button>
            </div>

            <!-- Escritorio: grilla del mes. Es una tabla de verdad (día × semana). -->
            <div class="hidden md:block" :aria-busy="loading">
                <div class="overflow-hidden rounded-xl transition-opacity" :class="loading ? 'pointer-events-none opacity-40' : 'opacity-100'">
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
                                <td
                                    v-for="day in row.days"
                                    :key="day.date"
                                    class="border border-brand-line/50 p-0 align-top"
                                    :class="day.in_month ? 'bg-white' : 'bg-brand-line/10'"
                                    :aria-current="day.is_today ? 'date' : undefined"
                                >
                                    <component
                                        :is="day.activities.length ? 'button' : 'div'"
                                        v-bind="
                                            day.activities.length
                                                ? { type: 'button', 'aria-haspopup': 'dialog', 'aria-label': dayAriaLabel(day.label, day.activities.length) }
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
                                        <span
                                            v-else
                                            class="px-0.5 text-[13px] font-semibold leading-none"
                                            :class="day.in_month ? 'text-brand-ink' : 'text-brand-muted/60'"
                                        >
                                            {{ day.day }}
                                        </span>

                                        <span
                                            v-for="activity in day.activities.slice(0, 2)"
                                            :key="activity.key"
                                            class="flex w-full min-w-0 items-center gap-1 rounded px-1 py-0.5 text-[11px] leading-tight ring-1"
                                            :class="[styleFor(styles, activity.source.slug).chip, day.in_month ? '' : 'opacity-60']"
                                        >
                                            <component
                                                :is="styleFor(styles, activity.source.slug).icon"
                                                class="h-3 w-3 shrink-0"
                                                :class="styleFor(styles, activity.source.slug).iconText"
                                                aria-hidden="true"
                                            />
                                            <span class="sr-only">{{ activity.source.title }}:</span>
                                            <span v-if="activity.start" class="shrink-0 font-semibold tabular-nums">{{ timeLabel(activity.start) }}</span>
                                            <span class="truncate">{{ activity.title }}</span>
                                        </span>

                                        <span v-if="day.activities.length > 2" class="pl-1 text-[11px] font-medium text-brand-sky">
                                            +{{ day.activities.length - 2 }} más
                                        </span>
                                    </component>
                                </td>
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
                        :disabled="loading"
                        aria-label="Semana anterior"
                        title="Semana anterior"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-brand-sky ring-1 ring-brand-line transition hover:bg-brand-light disabled:opacity-40"
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
                        :disabled="loading"
                        aria-label="Semana siguiente"
                        title="Semana siguiente"
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-brand-sky ring-1 ring-brand-line transition hover:bg-brand-light disabled:opacity-40"
                        @click="stepWeek(1)"
                    >
                        <ChevronRight class="h-5 w-5" />
                    </button>
                </div>

                <button
                    v-if="!weekHasToday"
                    type="button"
                    :disabled="loading"
                    class="mx-auto mb-3 block rounded-full px-3 py-1 text-xs font-medium uppercase tracking-wide text-brand-sky ring-1 ring-brand-sky/40 disabled:opacity-40"
                    @click="goToToday"
                >
                    volver a hoy
                </button>

                <ol class="space-y-2 transition-opacity" :aria-busy="loading" :class="loading ? 'opacity-40' : ''">
                    <li
                        v-for="(day, i) in week?.days ?? []"
                        :key="day.date"
                        class="overflow-hidden rounded-xl"
                        :class="day.is_today ? 'ring-2 ring-brand-sky' : 'ring-1 ring-brand-line/60'"
                    >
                        <div class="flex items-baseline gap-2 px-3 py-2" :class="day.in_month ? 'bg-brand-light/60' : 'bg-brand-line/20'">
                            <span
                                class="text-[11px] font-semibold uppercase tracking-wide"
                                :class="day.in_month ? 'text-brand-sky-dark' : 'text-brand-muted'"
                            >
                                {{ DIAS[i].short }}
                            </span>
                            <span
                                class="font-heading text-lg font-semibold leading-none"
                                :class="day.in_month ? 'text-brand-ink' : 'text-brand-muted'"
                            >
                                {{ day.day }}
                            </span>
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

            <p
                v-if="!hasActivities"
                class="mt-5 text-center font-display text-xl uppercase tracking-wide text-brand-muted"
            >
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
