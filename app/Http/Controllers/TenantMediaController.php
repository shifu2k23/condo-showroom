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

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path, headers: [
                'Cache-Control' => 'private, max-age=300',
            ]);
        }

        abort(404);
    }
}
