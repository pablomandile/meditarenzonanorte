<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Quién puede entrar al panel con "Continuar con Google".
 *
 * Son dos listas que se suman:
 *  - la del servidor (GOOGLE_ALLOWED_EMAILS en el .env): el dueño. Es fija y no se
 *    edita desde el panel, para que un error ahí no lo pueda dejar afuera.
 *  - la del panel (ajuste google_allowed_emails): las cuentas que el dueño habilita
 *    más adelante, sin tocar el servidor.
 *
 * Cualquier cuenta de estas listas entra al panel COMPLETO: acá no hay roles.
 */
class GoogleAccess
{
    /** ¿El login con Google está configurado en el servidor? */
    public static function configured(): bool
    {
        return (bool) config('services.google.client_id');
    }

    /**
     * Los emails fijos del servidor (el dueño).
     *
     * @return array<int, string>
     */
    public static function ownerEmails(): array
    {
        return self::parse(config('services.google.allowed_emails'));
    }

    /**
     * Los emails que el dueño habilitó desde el panel.
     *
     * @return array<int, string>
     */
    public static function panelEmails(): array
    {
        return self::parse(Setting::get('google_allowed_emails'));
    }

    /**
     * La lista efectiva: servidor + panel, sin repetidos.
     *
     * @return array<int, string>
     */
    public static function allowedEmails(): array
    {
        return array_values(array_unique([...self::ownerEmails(), ...self::panelEmails()]));
    }

    public static function isAllowed(string $email): bool
    {
        $email = Str::lower(trim($email));

        return $email !== '' && in_array($email, self::allowedEmails(), true);
    }

    /**
     * Parte un texto libre —separado por comas, punto y coma, espacios o saltos de
     * línea— en una lista de emails en minúscula y sin repetidos.
     *
     * @return array<int, string>
     */
    public static function parse(?string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', (string) $raw, flags: PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn ($email) => Str::lower(trim($email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Lo que se guarda en el ajuste: en minúscula, sin repetidos, sin los emails
     * que ya vienen fijos del servidor y uno por línea. Vacío se guarda como null.
     */
    public static function normalizeForStorage(?string $raw): ?string
    {
        $owner = self::ownerEmails();
        $emails = array_values(array_filter(
            self::parse($raw),
            fn ($email) => ! in_array($email, $owner, true),
        ));

        return $emails === [] ? null : implode("\n", $emails);
    }
}
