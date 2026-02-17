<?php

namespace App\Application\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class ImagePathPolicy
{
    public function unitDirectory(int|string $unitId): string
    {
        return "units/{$unitId}";
    }

    public function makeRelativePath(int|string $unitId, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;

        return "{$this->unitDirectory($unitId)}/{$filename}";
    }

    public function assertRelative(string $path): void
    {
        if ($path === '') {
            throw new InvalidArgumentException('Image path cannot be empty.');
        }

        if (str_starts_with($path, '/') || str_contains($path, '://')) {
            throw new InvalidArgumentException('Image path must be relative; absolute URLs are forbidden.');
        }

        if (str_starts_with($path, 'storage/')) {
            throw new InvalidArgumentException('Stored image path must not include public storage prefix.');
        }

        if (! str_starts_with($path, 'units/')) {
            throw new InvalidArgumentException('Image path must be namespaced under units/.');
        }
    }
}
