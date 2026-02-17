<?php

namespace App\Services;

use App\Models\UnitImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    public function storeUnitImage(UploadedFile $file, int $unitId): string
    {
        $directory = "units/{$unitId}";
        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        return $file->storeAs($directory, $filename, $this->disk());
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk())->delete($path);
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
        return config('filesystems.default');
    }
}
