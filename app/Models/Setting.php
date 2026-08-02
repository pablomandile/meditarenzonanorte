<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::values()[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * El icono de la pestaña: el logo del pie, y si no está cargado el del menú
     * — el mismo orden que usa SiteFooter.vue.
     *
     * Se prefiere el del pie porque suele ser el isotipo cuadrado, mientras que el
     * del menú es el logo ancho con el nombre al lado: reducido a 16 px no se lee.
     *
     * Devuelve la ruta y el tipo MIME deducido de la extensión (el archivo lo sube
     * el dueño, así que puede ser png, webp o jpg), o null si no hay ningún logo.
     *
     * @return array{path: string, type: ?string}|null
     */
    public static function favicon(): ?array
    {
        $path = static::get('footer_logo_path') ?: static::get('logo_path');

        if (! $path) {
            return null;
        }

        return [
            'path' => $path,
            'type' => match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'jpg', 'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                default => null,
            },
        ];
    }

    /**
     * All settings as a cached key => value map.
     *
     * @return array<string, string|null>
     */
    public static function values(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }
}
