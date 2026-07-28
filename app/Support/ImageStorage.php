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

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $copy = dirname($path).'/'.Str::random(40).($extension === '' ? '' : '.'.$extension);

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
        foreach (self::DELETABLE_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
