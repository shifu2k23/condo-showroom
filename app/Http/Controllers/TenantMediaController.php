<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\UnitImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class TenantMediaController extends Controller
{
    public function showUnitImage(Tenant $tenant, UnitImage $unitImage): Response
    {
        if ((int) $unitImage->tenant_id !== (int) $tenant->getKey()) {
            abort(404);
        }

        $path = $unitImage->path;
        $expectedPrefix = 'tenants/'.$tenant->getKey().'/';

        if (! str_starts_with($path, $expectedPrefix)) {
            abort(404);
        }

        foreach ($this->candidateDisks() as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            return Storage::disk($disk)->response($path, headers: [
                'Cache-Control' => 'private, max-age=300',
            ]);
        }

        abort(404);
    }

    /**
     * @return array<int, string>
     */
    private function candidateDisks(): array
    {
        $configuredDisk = (string) config('filesystems.unit_images_disk', 'local');

        return collect([$configuredDisk, 'local', 'public'])
            ->filter(fn (string $disk): bool => config("filesystems.disks.{$disk}") !== null)
            ->unique()
            ->values()
            ->all();
    }
}
