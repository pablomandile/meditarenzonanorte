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
    date_text: string | null;
    starts_at: string | null;
    ends_at: string | null;
    start_time: string | null;
    end_time: string | null;
    location: string | null;
    price: string | null;
    cta_label: string | null;
    cta_url: string | null;
    image_path: string | null;
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
 * Búsqueda en Google Maps para una dirección escrita a mano (el campo "lugar"
 * de las clases y los eventos es texto libre, no una coordenada).
 */
export function mapsUrl(query?: string | null): string | undefined {
    return query ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}` : undefined;
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
