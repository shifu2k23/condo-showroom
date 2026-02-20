<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\UnitImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public function storeUnitImage(UploadedFile $file, int $unitId): string
    {
        $unit = Unit::query()->select(['id', 'tenant_id'])->findOrFail($unitId);
        $directory = "tenants/{$unit->tenant_id}/units/{$unitId}";
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, $this->disk());
    }

    public function delete(string $path): bool
    {
        foreach ($this->candidateDisks() as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    public function reorderUnitImages(int $unitId, array $orderedIds): void
    {
        DB::transaction(function () use ($unitId, $orderedIds): void {
            foreach ($orderedIds as $sortOrder => $id) {
                UnitImage::query()
                    ->where('unit_id', $unitId)
                    ->whereKey($id)
                    ->update(['sort_order' => $sortOrder]);
            }
        });
    }

    private function disk(): string
    {
        $configuredDisk = (string) config('filesystems.unit_images_disk', 'local');

        return config("filesystems.disks.{$configuredDisk}") !== null
            ? $configuredDisk
            : 'local';
    }

    /**
     * @return array<int, string>
     */
    private function candidateDisks(): array
    {
        return collect([$this->disk(), 'local', 'public'])
            ->filter(fn (string $disk): bool => config("filesystems.disks.{$disk}") !== null)
            ->unique()
            ->values()
            ->all();
    }
}
