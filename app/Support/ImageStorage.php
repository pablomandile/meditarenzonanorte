<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        foreach (self::DELETABLE_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                Storage::disk('public')->delete($path);

                return;
            }
        }
    }
}
