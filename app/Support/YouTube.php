<?php

namespace App\Support;

/**
 * Saca el id de un video de YouTube de cualquiera de las formas en que se pega un
 * enlace: la barra del navegador, el botón "Compartir", `youtu.be`, un Short, un
 * embed, o el id pelado. Con eso se arma la URL para el iframe.
 */
class YouTube
{
    public static function id(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        // El id pelado (11 caracteres).
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $url)) {
            return $url;
        }

        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{11})~',
            '~youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)([A-Za-z0-9_-]{11})~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /** La URL del iframe. youtube-nocookie no cambia nada acá, pero es lo prolijo. */
    public static function embedUrl(?string $url): ?string
    {
        $id = self::id($url);

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}" : null;
    }

    /** La miniatura, para no cargar el reproductor entero hasta que haga falta. */
    public static function thumbnailUrl(?string $url): ?string
    {
        $id = self::id($url);

        return $id ? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg" : null;
    }
}
