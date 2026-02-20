<?php

namespace App\Livewire\Super\Tenants;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.super')]
class Index extends Component
{
    public string $name = '';

    public string $slug = '';

    public int $trialDays = 14;

    public bool $createAdmin = true;

    public string $adminName = '';

    public string $adminEmail = '';

    public int $extendDays = 30;

    public function createTenant(): void
    {
        $baseRules = [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'trialDays' => ['required', 'integer', 'min:1', 'max:3650'],
            'createAdmin' => ['required', 'boolean'],
        ];

        $adminRules = $this->createAdmin
            ? [
                'adminName' => ['required', 'string', 'max:120'],
                'adminEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            ]
            : [];

        $validated = $this->validate($baseRules + $adminRules);
        $generatedAdminPassword = null;
        $generatedAdminEmail = null;

        DB::transaction(function () use ($validated, &$generatedAdminPassword, &$generatedAdminEmail): void {
            $requestedSlug = trim((string) ($validated['slug'] ?? ''));
            $tenantSlug = $requestedSlug !== ''
                ? $requestedSlug
                : $this->makeUniqueSlug(trim($validated['name']));

            $tenant = Tenant::query()->create([
                'name' => trim($validated['name']),
                'slug' => $tenantSlug,
                'is_disabled' => false,
                'trial_ends_at' => now()->addDays((int) $validated['trialDays']),
            ]);

            if (! $validated['createAdmin']) {
                return;
            }

            $generatedAdminPassword = Str::password(20);
            $generatedAdminEmail = strtolower(trim($validated['adminEmail']));

            User::query()->create([
                'tenant_id' => $tenant->id,
                'name' => trim($validated['adminName']),
                'email' => $generatedAdminEmail,
                'password' => Hash::make($generatedAdminPassword),
                'email_verified_at' => now(),
                'is_admin' => true,
                'is_super_admin' => false,
            ]);
        });

        $this->reset(['name', 'slug', 'adminName', 'adminEmail']);
        $this->trialDays = 14;
        session()->flash('status', 'Tenant created successfully.');

        if ($generatedAdminPassword !== null && $generatedAdminEmail !== null) {
            session()->flash('generated_admin_credentials', [
                'email' => $generatedAdminEmail,
                'password' => $generatedAdminPassword,
            ]);
        }
    }

    public function toggleDisabled(int $tenantId): void
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $tenant->forceFill([
            'is_disabled' => ! $tenant->is_disabled,
        ])->save();
    }

    public function extendTrial(int $tenantId): void
    {
        $validated = $this->validate([
            'extendDays' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $tenant = Tenant::query()->findOrFail($tenantId);
        $baseDate = $tenant->trial_ends_at !== null && $tenant->trial_ends_at->isFuture()
            ? $tenant->trial_ends_at
            : now();

        $tenant->forceFill([
            'trial_ends_at' => $baseDate->copy()->addDays((int) $validated['extendDays']),
        ])->save();
    }

    public function shareableTenantUrl(Tenant $tenant): string
    {
        return rtrim((string) config('app.url'), '/').'/t/'.$tenant->slug;
    }

    public function render()
    {
        return view('livewire.super.tenants.index', [
            'tenants' => Tenant::query()
                ->withCount(['users', 'units'])
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    private function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'tenant';
        }

        $slug = $base;
        $suffix = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
