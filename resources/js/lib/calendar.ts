import { CalendarDays, Repeat, Sparkles, Star } from 'lucide-vue-next';
import type { Component } from 'vue';

export type CalendarSource = { slug: string; title: string; url: string | null };

export type CalendarActivityData = {
    key: string;
    kind: 'clase' | 'evento';
    title: string;
    start: string | null;
    end: string | null;
    time_text: string | null;
    location: string | null;
    price: string | null;
    cta_label: string | null;
    cta_url: string | null;
    image_path: string | null;
    source: CalendarSource;
};

export type CalendarDay = {
    date: string;
    day: number;
    label: string;
    in_month: boolean;
    is_today: boolean;
    activities: CalendarActivityData[];
};

export type CalendarWeek = { label: string; days: CalendarDay[] };

export type CalendarData = {
    month: string;
    label: string;
    today: string;
    prev: string;
    next: string;
    weekdays: string[];
    sources: CalendarSource[];
    weeks: CalendarWeek[];
};

/** Los días de la semana, empezando el lunes como la grilla del servidor. */
export const DIAS = [
    { short: 'lun', long: 'lunes' },
    { short: 'mar', long: 'martes' },
    { short: 'mié', long: 'miércoles' },
    { short: 'jue', long: 'jueves' },
    { short: 'vie', long: 'viernes' },
    { short: 'sáb', long: 'sábado' },
    { short: 'dom', long: 'domingo' },
];

/** '19:00' → '19'; '20:15' → '20.15' (el sitio escribe los minutos con punto). */
export function timeLabel(time?: string | null): string {
    if (!time) return '';

    const [hours, minutes] = time.split(':');

    return minutes === '00' ? hours.replace(/^0/, '') : `${hours.replace(/^0/, '')}.${minutes}`;
}

/** '19:00' + '20:15' → '19 a 20.15 hs'. Sin fin: '19 hs'. Sin inicio: ''. */
export function timeRange(start?: string | null, end?: string | null): string {
    if (!start) return '';

    return end ? `${timeLabel(start)} a ${timeLabel(end)} hs` : `${timeLabel(start)} hs`;
}

export type SourceStyle = {
    chip: string;
    icon: Component;
    iconText: string;
    dot: string;
    bar: string;
    label: string;
};

/**
 * El texto de las píldoras es siempre brand-ink: brand-sky-dark sobre brand-light
 * da 3.95:1, suficiente para un ícono pero no para 11px de texto. brand-pale nunca
 * pinta un punto ni una letra (1.6:1 sobre blanco), solo hace de fondo.
 *
 * Cada color viene con un ícono propio: el color nunca es la única señal.
 */
const PALETTE: SourceStyle[] = [
    {
        chip: 'bg-brand-light ring-brand-sky/40 text-brand-ink',
        icon: Repeat,
        iconText: 'text-brand-sky-dark',
        dot: 'bg-brand-sky',
        bar: 'border-brand-sky',
        label: 'text-brand-sky-dark',
    },
    {
        chip: 'bg-brand-cream ring-brand-orange/40 text-brand-ink',
        icon: Sparkles,
        iconText: 'text-brand-orange-dark',
        dot: 'bg-brand-orange',
        bar: 'border-brand-orange',
        label: 'text-brand-orange-dark',
    },
    {
        chip: 'bg-brand-line/30 ring-brand-line text-brand-ink',
        icon: Star,
        iconText: 'text-brand-body',
        dot: 'bg-brand-body',
        bar: 'border-brand-body',
        label: 'text-brand-body',
    },
    {
        chip: 'bg-brand-pale/40 ring-brand-sky-dark/40 text-brand-ink',
        icon: CalendarDays,
        iconText: 'text-brand-sky-dark',
        dot: 'bg-brand-sky-dark',
        bar: 'border-brand-sky-dark',
        label: 'text-brand-sky-dark',
    },
];

/**
 * Un color por fuente, por su posición en la lista que manda el servidor (que no
 * depende del mes): así una actividad no cambia de color al navegar los meses.
 */
export function sourceStyles(sources: CalendarSource[]): Map<string, SourceStyle> {
    return new Map(sources.map((source, i) => [source.slug, PALETTE[i % PALETTE.length]]));
}

export function styleFor(styles: Map<string, SourceStyle>, slug?: string | null): SourceStyle {
    return (slug && styles.get(slug)) || PALETTE[PALETTE.length - 1];
}
