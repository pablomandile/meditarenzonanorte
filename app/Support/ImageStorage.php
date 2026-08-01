<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageStorage
{
    /**
     * Folders that hold admin-uploaded files and are safe to clean up.
     * Seeded images (seed/...) are never deleted: they may be shared
     * between sections and are restored by the seeder.
     */
    private const DELETABLE_PREFIXES = ['sections/', 'events/', 'settings/'];

    /**
     * Folders the image gallery lists and this class recognises. A submitted
     * path outside them is rejected: nothing in the panel produces one.
     */
    public const PICKABLE_PREFIXES = ['sections/', 'events/', 'settings/', 'seed/'];

    public static function store(UploadedFile $file, string $folder): string
    {
        return $file->store($folder, 'public');
    }

    public static function replace(UploadedFile $file, string $folder, ?string $oldPath): string
    {
        self::delete($oldPath);

        return self::store($file, $folder);
    }

    /**
     * Copies an uploaded file so the duplicate owns it: replacing the image on
     * one section must not delete the file the other one still points at.
     * Seeded images (seed/...) are never deleted, so their path is shared as is.
     */
    public static function duplicate(?string $path): ?string
    {
        if ($path === null || $path === '' || ! self::isDeletable($path)) {
            return $path;
        }

        return self::copyInto($path, dirname($path));
    }

    /**
     * Takes ownership of an image picked from the gallery: the file still belongs
     * to another record, so it is copied into $folder. Without the copy,
     * replacing the image here later would delete the file the other record
     * still points at. Paths already owned pass through untouched, and so do
     * seeded images (seed/...), which are shared on purpose and never deleted.
     *
     * @param  array<int, string>  $owned  paths the record already stores
     */
    public static function adopt(?string $path, array $owned, string $folder): ?string
    {
        if ($path === null || $path === '' || in_array($path, $owned, true)) {
            return $path;
        }

        if (! self::isPickable($path)) {
            return null;
        }

        if (! self::isDeletable($path)) {
            return $path;
        }

        return self::copyInto($path, $folder);
    }

    private static function copyInto(string $path, string $folder): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $copy = $folder.'/'.Str::random(40).($extension === '' ? '' : '.'.$extension);

        $disk->copy($path, $copy);

        return $copy;
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '' || ! self::isDeletable($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private static function isDeletable(string $path): bool
    {
        return self::hasPrefix($path, self::DELETABLE_PREFIXES);
    }

    public static function isPickable(string $path): bool
    {
        return self::hasPrefix($path, self::PICKABLE_PREFIXES);
    }

    /**
     * @param  array<int, string>  $prefixes
     */
    private static function hasPrefix(string $path, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
