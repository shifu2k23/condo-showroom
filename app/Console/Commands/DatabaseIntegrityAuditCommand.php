<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseIntegrityAuditCommand extends Command
{
    private const INVALID_OPTION = -1;

    protected $signature = 'health:db-integrity
        {--tenant-id= : Limit checks to a specific tenant id}
        {--sample=10 : Number of sample IDs to include per issue}
        {--skip-storage : Skip storage existence checks for unit image files}
        {--fail-on-warning : Return non-zero exit code when warning issues exist}';

    protected $description = 'Audit tenant/unit/unit_images integrity and report data quality issues';

    public function handle(): int
    {
        $tenantId = $this->resolveTenantId();
        if ($tenantId === self::INVALID_OPTION) {
            return self::INVALID;
        }

        $sampleLimit = max(1, (int) $this->option('sample'));
        $skipStorage = (bool) $this->option('skip-storage');
        $failOnWarning = (bool) $this->option('fail-on-warning');

        $issues = [];

        $this->line('Running database integrity audit...');
        if (is_int($tenantId)) {
            $this->line("Tenant scope: {$tenantId}");
        }

        $unitsMissingTenant = DB::table('units as u')
            ->leftJoin('tenants as t', 't.id', '=', 'u.tenant_id')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('u.tenant_id', $tenantId))
            ->where(function (Builder $query): void {
                $query->whereNull('u.tenant_id')->orWhereNull('t.id');
            });
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'units_missing_valid_tenant',
            count: (int) $unitsMissingTenant->count(),
            samples: $this->sampleIds(clone $unitsMissingTenant, 'u.id', $sampleLimit),
        );

        $unitsMissingCategory = DB::table('units as u')
            ->leftJoin('categories as c', 'c.id', '=', 'u.category_id')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('u.tenant_id', $tenantId))
            ->whereNull('c.id');
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'units_missing_category',
            count: (int) $unitsMissingCategory->count(),
            samples: $this->sampleIds(clone $unitsMissingCategory, 'u.id', $sampleLimit),
        );

        $unitImagesMissingUnit = DB::table('unit_images as ui')
            ->leftJoin('units as u', 'u.id', '=', 'ui.unit_id')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('ui.tenant_id', $tenantId))
            ->whereNull('u.id');
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'unit_images_missing_unit',
            count: (int) $unitImagesMissingUnit->count(),
            samples: $this->sampleIds(clone $unitImagesMissingUnit, 'ui.id', $sampleLimit),
        );

        $unitImagesTenantMismatch = DB::table('unit_images as ui')
            ->join('units as u', 'u.id', '=', 'ui.unit_id')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('ui.tenant_id', $tenantId))
            ->whereColumn('ui.tenant_id', '!=', 'u.tenant_id');
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'unit_images_tenant_mismatch',
            count: (int) $unitImagesTenantMismatch->count(),
            samples: $this->sampleIds(clone $unitImagesTenantMismatch, 'ui.id', $sampleLimit),
        );

        $unitImagesMissingPublicId = DB::table('unit_images as ui')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('ui.tenant_id', $tenantId))
            ->where(function (Builder $query): void {
                $query->whereNull('ui.public_id')->orWhere('ui.public_id', '');
            });
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'unit_images_missing_public_id',
            count: (int) $unitImagesMissingPublicId->count(),
            samples: $this->sampleIds(clone $unitImagesMissingPublicId, 'ui.id', $sampleLimit),
        );

        $duplicateUnitPublicIds = DB::table('units as u')
            ->select('u.public_id')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('u.tenant_id', $tenantId))
            ->groupBy('u.public_id')
            ->havingRaw('count(*) > 1');
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'units_duplicate_public_id',
            count: (int) $duplicateUnitPublicIds->count(),
            samples: $this->sampleGroupedValues(clone $duplicateUnitPublicIds, 'u.public_id', $sampleLimit),
        );

        $duplicateUnitImagePublicIds = DB::table('unit_images as ui')
            ->select('ui.public_id')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('ui.tenant_id', $tenantId))
            ->groupBy('ui.public_id')
            ->havingRaw('count(*) > 1');
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'unit_images_duplicate_public_id',
            count: (int) $duplicateUnitImagePublicIds->count(),
            samples: $this->sampleGroupedValues(clone $duplicateUnitImagePublicIds, 'ui.public_id', $sampleLimit),
        );

        $activeUnitsWithoutImages = DB::table('units as u')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('u.tenant_id', $tenantId))
            ->whereNull('u.deleted_at')
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('unit_images as ui')
                    ->whereColumn('ui.unit_id', 'u.id');
            });
        $issues[] = $this->issue(
            severity: 'warning',
            check: 'active_units_without_images',
            count: (int) $activeUnitsWithoutImages->count(),
            samples: $this->sampleIds(clone $activeUnitsWithoutImages, 'u.id', $sampleLimit),
        );

        $availableUnitsWithoutImages = DB::table('units as u')
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('u.tenant_id', $tenantId))
            ->whereNull('u.deleted_at')
            ->where('u.status', 'AVAILABLE')
            ->whereNotExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('unit_images as ui')
                    ->whereColumn('ui.unit_id', 'u.id');
            });
        $issues[] = $this->issue(
            severity: 'warning',
            check: 'available_units_without_images',
            count: (int) $availableUnitsWithoutImages->count(),
            samples: $this->sampleIds(clone $availableUnitsWithoutImages, 'u.id', $sampleLimit),
        );

        [$pathPrefixMismatchCount, $pathPrefixMismatchSamples] = $this->pathPrefixMismatchStats($tenantId, $sampleLimit);
        $issues[] = $this->issue(
            severity: 'critical',
            check: 'unit_images_path_prefix_mismatch',
            count: $pathPrefixMismatchCount,
            samples: $pathPrefixMismatchSamples,
        );

        if (! $skipStorage) {
            [$missingFilesCount, $missingFilesSamples] = $this->missingImageFileStats($tenantId, $sampleLimit);
            $issues[] = $this->issue(
                severity: 'critical',
                check: 'unit_images_missing_files_on_storage',
                count: $missingFilesCount,
                samples: $missingFilesSamples,
            );
        }

        $this->newLine();
        $this->table(
            ['Severity', 'Check', 'Count', 'Samples', 'Status'],
            array_map(
                fn (array $issue): array => [
                    strtoupper($issue['severity']),
                    $issue['check'],
                    (string) $issue['count'],
                    $issue['samples'] === [] ? '-' : implode(', ', $issue['samples']),
                    $issue['count'] > 0 ? 'ISSUE' : 'OK',
                ],
                $issues
            )
        );

        $criticalCount = array_sum(array_map(
            fn (array $issue): int => $issue['severity'] === 'critical' ? $issue['count'] : 0,
            $issues
        ));
        $warningCount = array_sum(array_map(
            fn (array $issue): int => $issue['severity'] === 'warning' ? $issue['count'] : 0,
            $issues
        ));

        $this->newLine();
        $this->line("Critical issues: {$criticalCount}");
        $this->line("Warning issues: {$warningCount}");

        if ($criticalCount > 0) {
            $this->error('Integrity audit failed with critical issues.');

            return self::FAILURE;
        }

        if ($failOnWarning && $warningCount > 0) {
            $this->error('Integrity audit failed due to warning issues (--fail-on-warning enabled).');

            return self::FAILURE;
        }

        $this->info('Integrity audit completed.');

        return self::SUCCESS;
    }

    private function resolveTenantId(): ?int
    {
        $option = $this->option('tenant-id');
        if ($option === null || $option === '') {
            return null;
        }

        if (! is_numeric($option) || (int) $option <= 0) {
            $this->error('Invalid --tenant-id. Provide a positive integer.');

            return self::INVALID_OPTION;
        }

        return (int) $option;
    }

    /**
     * @return array{severity:string,check:string,count:int,samples:array<int,string>}
     */
    private function issue(string $severity, string $check, int $count, array $samples): array
    {
        return [
            'severity' => $severity,
            'check' => $check,
            'count' => $count,
            'samples' => $samples,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function sampleIds(Builder $query, string $column, int $limit): array
    {
        return $query
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->map(fn ($value): string => (string) $value)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function sampleGroupedValues(Builder $query, string $column, int $limit): array
    {
        return $query
            ->orderBy($column)
            ->limit($limit)
            ->pluck($column)
            ->map(fn ($value): string => (string) $value)
            ->values()
            ->all();
    }

    /**
     * @return array{0:int,1:array<int,string>}
     */
    private function pathPrefixMismatchStats(?int $tenantId, int $sampleLimit): array
    {
        $count = 0;
        $samples = [];

        DB::table('unit_images as ui')
            ->select(['ui.id', 'ui.tenant_id', 'ui.path'])
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('ui.tenant_id', $tenantId))
            ->orderBy('ui.id')
            ->chunkById(500, function ($rows) use (&$count, &$samples, $sampleLimit): void {
                foreach ($rows as $row) {
                    $normalizedPath = ltrim((string) $row->path, '/');
                    $expectedPrefix = 'tenants/'.$row->tenant_id.'/';
                    if (str_starts_with($normalizedPath, $expectedPrefix)) {
                        continue;
                    }

                    $count++;
                    if (count($samples) < $sampleLimit) {
                        $samples[] = (string) $row->id;
                    }
                }
            }, 'ui.id', 'id');

        return [$count, $samples];
    }

    /**
     * @return array{0:int,1:array<int,string>}
     */
    private function missingImageFileStats(?int $tenantId, int $sampleLimit): array
    {
        $disks = $this->candidateDisks();
        $count = 0;
        $samples = [];

        DB::table('unit_images as ui')
            ->select(['ui.id', 'ui.tenant_id', 'ui.path'])
            ->when(is_int($tenantId), fn (Builder $query) => $query->where('ui.tenant_id', $tenantId))
            ->orderBy('ui.id')
            ->chunkById(300, function ($rows) use (&$count, &$samples, $sampleLimit, $disks): void {
                foreach ($rows as $row) {
                    if ($this->fileExistsOnAnyDisk((string) $row->path, $disks)) {
                        continue;
                    }

                    $count++;
                    if (count($samples) < $sampleLimit) {
                        $samples[] = (string) $row->id;
                    }
                }
            }, 'ui.id', 'id');

        return [$count, $samples];
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

    private function fileExistsOnAnyDisk(string $path, array $disks): bool
    {
        foreach ($disks as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return true;
            }
        }

        return false;
    }
}
