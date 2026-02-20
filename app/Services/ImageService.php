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
        if (Storage::disk($this->disk())->exists($path)) {
            return Storage::disk($this->disk())->delete($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
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
        return 'local';
    }
}
