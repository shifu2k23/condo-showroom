<?php

namespace App\Console\Commands;

use App\Models\Unit;
use App\Models\UnitImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RepairUnitImages extends Command
{
    protected $signature = 'media:repair-unit-images
        {--prune-missing : Delete image DB rows when file is missing from storage}
        {--chunk=200 : Number of records to process per chunk}';

    protected $description = 'Repair tenant metadata for unit images and optionally prune records with missing files';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $pruneMissing = (bool) $this->option('prune-missing');
        $preferredDisk = (string) config('filesystems.unit_images_disk', 'local');
        $disks = collect([$preferredDisk, 'local', 'public'])
            ->filter(fn (string $disk): bool => config("filesystems.disks.{$disk}") !== null)
            ->unique()
            ->values()
            ->all();

        $stats = [
            'processed' => 0,
            'updated_tenant' => 0,
            'updated_path' => 0,
            'deleted_missing' => 0,
            'deleted_orphan' => 0,
            'missing_files' => 0,
        ];

        $this->info('Repairing unit_images...');
        $this->line('Disks checked: '.implode(', ', $disks));

        UnitImage::query()
            ->withoutGlobalScope('tenant')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($images) use (&$stats, $disks, $pruneMissing): void {
                foreach ($images as $image) {
                    $stats['processed']++;

                    $unit = Unit::query()
                        ->withoutGlobalScope('tenant')
                        ->select(['id', 'tenant_id'])
                        ->find($image->unit_id);

                    if (! $unit) {
                        DB::table('unit_images')->where('id', $image->id)->delete();
                        $stats['deleted_orphan']++;
                        continue;
                    }

                    $expectedTenantId = (int) $unit->tenant_id;
                    if ((int) $image->tenant_id !== $expectedTenantId) {
                        DB::table('unit_images')
                            ->where('id', $image->id)
                            ->update(['tenant_id' => $expectedTenantId]);
                        $stats['updated_tenant']++;
                    }

                    $expectedPrefix = 'tenants/'.$expectedTenantId.'/';
                    if (! str_starts_with((string) $image->path, $expectedPrefix)) {
                        $rewrittenPath = $this->rewritePathPrefix((string) $image->path, $expectedPrefix);
                        if ($rewrittenPath !== null) {
                            DB::table('unit_images')
                                ->where('id', $image->id)
                                ->update(['path' => $rewrittenPath]);
                            $image->path = $rewrittenPath;
                            $stats['updated_path']++;
                        }
                    }

                    if ($this->fileExistsOnAnyDisk((string) $image->path, $disks)) {
                        continue;
                    }

                    $stats['missing_files']++;

                    if ($pruneMissing) {
                        DB::table('unit_images')->where('id', $image->id)->delete();
                        $stats['deleted_missing']++;
                    }
                }
            });

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn (int $value, string $key): array => [$key, (string) $value])->values()->all()
        );

        if ($stats['missing_files'] > 0 && ! $pruneMissing) {
            $this->warn('Missing files detected. Re-run with --prune-missing to remove broken DB rows.');
        }

        return self::SUCCESS;
    }

    private function fileExistsOnAnyDisk(string $path, array $disks): bool
    {
        foreach ($disks as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return true;
            }
        }

        return false;
    }

    private function rewritePathPrefix(string $path, string $expectedPrefix): ?string
    {
        $normalizedPath = ltrim($path, '/');

        if (str_starts_with($normalizedPath, 'tenants/')) {
            $segments = explode('/', $normalizedPath, 3);
            if (count($segments) === 3) {
                return $expectedPrefix.$segments[2];
            }

            return null;
        }

        if ($normalizedPath === '') {
            return null;
        }

        return $expectedPrefix.$normalizedPath;
    }
}

