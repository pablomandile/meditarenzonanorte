<?php

namespace App\Support;

use App\Models\Setting;

/**
 * El enlace del botón flotante de WhatsApp: el número (whatsapp_url) y el
 * mensaje precargado (whatsapp_message) se guardan por separado en Ajustes y se
 * componen acá en un solo link.
 *
 * Antes el mensaje vivía adentro de whatsapp_url, como el parámetro ?text= de la
 * URL: cambiarlo significaba editar un texto codificado a mano
 * ("Hola%2C%20me%20gustar%C3%ADa..."). baseUrl() le saca ese resto también a lo
 * que ya está guardado así de instalaciones viejas, sin necesidad de migrar la
 * base: la próxima vez que se guarde desde el panel, whatsapp_url queda limpio.
 */
class WhatsApp
{
    /** El número sin el mensaje, tal como se edita en el panel. */
    public static function baseUrl(): ?string
    {
        $url = Setting::get('whatsapp_url');

        return $url ? strtok($url, '?') : null;
    }

    /** El link final del botón flotante, con el mensaje precargado si hay uno. */
    public static function link(): ?string
    {
        $base = self::baseUrl();

        if (! $base) {
            return null;
        }

        $message = Setting::get('whatsapp_message');

        return $message ? $base.'?text='.rawurlencode($message) : $base;
    }
}
