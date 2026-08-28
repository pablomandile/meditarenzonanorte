export type SectionData = {
    id: number;
    type: string;
    key: string;
    content: Record<string, any>;
};

export type EventData = {
    id: number;
    title: string;
    description: string | null;
    /** Texto escrito a mano; si está vacío se publica date_auto. */
    date_text: string | null;
    /** Armado con la fecha y la hora, siempre presente si hay fecha de inicio. */
    date_auto: string | null;
    /** Lo que se muestra: date_text si lo hay, y si no date_auto. */
    date_label: string | null;
    starts_at: string | null;
    ends_at: string | null;
    start_time: string | null;
    end_time: string | null;
    location: string | null;
    price: string | null;
    cta_label: string | null;
    cta_url: string | null;
    image_path: string | null;
    image_url: string | null;
    visible: boolean;
    show_on_home: boolean;
    show_on_calendar: boolean;
};

export type FaqItem = { question: string; answer: string };

export type LinkItem = { label: string | null; url: string | null };

export type CardItem = {
    image: string | null;
    title: string | null;
    text: string | null;
    url: string | null;
};

/** Resolves a storage-relative image path to its public URL. */
export function img(path?: string | null): string | undefined {
    return path ? `/storage/${path}` : undefined;
}

/**
 * El enlace a Instagram a partir del ajuste del panel, que en la práctica se carga
 * de las tres maneras: la URL entera, `instagram.com/cuenta` sin el esquema, o el
 * usuario suelto (`@cuenta` o `cuenta`).
 *
 * Hace falta porque un valor sin esquema es una URL **relativa**: el navegador la
 * resuelve contra el propio sitio y el click termina en `/cuenta` en vez de en
 * Instagram, sin dar ningún error.
 */
export function instagramUrl(value?: string | null): string | undefined {
    const raw = value?.trim();

    if (!raw) return undefined;

    if (/^https?:\/\//i.test(raw)) return raw;

    // Tiene el dominio pero le falta el esquema.
    if (/^(www\.)?instagram\.com\//i.test(raw)) return `https://${raw}`;

    // Lo que queda es el usuario, con o sin arroba y con o sin barras sueltas.
    return `https://www.instagram.com/${raw.replace(/^\/+|\/+$/g, '').replace(/^@/, '')}`;
}

/**
 * Búsqueda en Google Maps para una dirección escrita a mano (el campo "lugar"
 * de las clases y los eventos es texto libre, no una coordenada).
 */
export function mapsUrl(query?: string | null): string | undefined {
    return query ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}` : undefined;
}

/**
 * Nombres separados por coma, enumerados como se dice en castellano:
 * "Ana" → "Ana"; "Ana, Luis" → "Ana y Luis"; "Ana, Luis, Sol" → "Ana, Luis y Sol".
 * Devuelve null si no hay ninguno, así el llamador decide si muestra algo.
 */
export function joinNames(text?: string | null): string | null {
    const names = (text ?? '')
        .split(',')
        .map((name) => name.trim())
        .filter(Boolean);

    if (!names.length) return null;

    const last = names.pop() as string;

    return names.length ? `${names.join(', ')} y ${last}` : last;
}

/** Divide un texto en líneas no vacías (una por renglón), para listas. */
export function lines(text?: string | null): string[] {
    if (!text) return [];

    return text
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
}

/** Splits a plain-text body into paragraphs (blank-line separated). */
export function paragraphs(text?: string | null): string[] {
    if (!text) return [];

    return text
        .split(/\n\s*\n/)
        .map((p) => p.trim())
        .filter(Boolean);
}

/** True when a URL points inside this site. */
export function isInternal(url?: string | null): boolean {
    return !!url && url.startsWith('/');
}
