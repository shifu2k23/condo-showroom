<?php

namespace App\Livewire\Super\Tenants;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.super')]
class Index extends Component
{
    private const DEFAULT_INITIAL_ADMIN_PASSWORD = '12345678';

    public string $name = '';

    public int $trialDays = 14;

    public bool $createAdmin = true;

    public string $adminName = '';

    public string $adminEmail = '';

    public int $extendDays = 30;

    public ?int $accountTenantId = null;

    public string $accountName = '';

    public string $accountEmail = '';

    public bool $accountIsAdmin = true;

    public string $accountPassword = '';

    public string $accountPasswordConfirmation = '';

    public ?int $editingAccountId = null;

    public ?int $passwordAccountId = null;

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $this->accountTenantId = $this->defaultTenantId();
    }

    public function createTenant(): void
    {
        $baseRules = [
            'name' => ['required', 'string', 'max:120'],
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
            $tenantSlug = $this->makeUniqueSlug(trim($validated['name']));

            $tenant = Tenant::query()->create([
                'name' => trim($validated['name']),
                'slug' => $tenantSlug,
                'is_disabled' => false,
                'trial_ends_at' => now()->addDays((int) $validated['trialDays']),
            ]);

            if (! $validated['createAdmin']) {
                return;
            }

            $generatedAdminPassword = self::DEFAULT_INITIAL_ADMIN_PASSWORD;
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

        $this->reset(['name', 'adminName', 'adminEmail']);
        $this->trialDays = 14;
        session()->flash('status', 'Tenant created successfully.');

        if ($generatedAdminPassword !== null && $generatedAdminEmail !== null) {
            session()->flash('generated_admin_credentials', [
                'email' => $generatedAdminEmail,
                'password' => $generatedAdminPassword,
            ]);
        }
    }

    public function saveAccount(): void
    {
        $emailUniqueRule = Rule::unique((new User())->getTable(), 'email');
        if ($this->editingAccountId !== null) {
            $emailUniqueRule = $emailUniqueRule->ignore($this->editingAccountId);
        }

        $validated = $this->validate([
            'accountTenantId' => ['required', 'integer', Rule::exists((new Tenant())->getTable(), 'id')],
            'accountName' => ['required', 'string', 'max:120'],
            'accountEmail' => ['required', 'email', 'max:255', $emailUniqueRule],
            'accountIsAdmin' => ['required', 'boolean'],
            'accountPassword' => [
                $this->editingAccountId === null ? 'required' : 'nullable',
                'string',
                'min:8',
                'max:255',
            ],
            'accountPasswordConfirmation' => ['nullable', 'required_with:accountPassword', 'same:accountPassword'],
        ]);

        $payload = [
            'tenant_id' => (int) $validated['accountTenantId'],
            'name' => trim($validated['accountName']),
            'email' => strtolower(trim($validated['accountEmail'])),
            'is_admin' => (bool) $validated['accountIsAdmin'],
            'is_super_admin' => false,
        ];

        $hasPasswordUpdate = is_string($validated['accountPassword']) && $validated['accountPassword'] !== '';
        if ($hasPasswordUpdate) {
            $payload['password'] = Hash::make($validated['accountPassword']);
        }

        if ($this->editingAccountId !== null) {
            $account = $this->managedAccounts()->findOrFail($this->editingAccountId);
            $account->forceFill($payload)->save();

            session()->flash('account_status', $hasPasswordUpdate
                ? 'Account and password updated successfully.'
                : 'Account updated successfully.');
        } else {
            $payload['password'] = Hash::make($validated['accountPassword']);
            $payload['email_verified_at'] = now();
            User::query()->create($payload);

            session()->flash('account_status', 'Account created successfully.');
        }

        $this->resetAccountForm();
    }

    public function editAccount(int $accountId): void
    {
        $account = $this->managedAccounts()->findOrFail($accountId);

        $this->editingAccountId = $account->id;
        $this->accountTenantId = $account->tenant_id;
        $this->accountName = $account->name;
        $this->accountEmail = $account->email;
        $this->accountIsAdmin = (bool) $account->is_admin;
        $this->accountPassword = '';
        $this->accountPasswordConfirmation = '';
        $this->passwordAccountId = null;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->resetValidation();
    }

    public function cancelAccountEdit(): void
    {
        $this->resetAccountForm();
    }

    public function deleteAccount(int $accountId): void
    {
        $account = $this->managedAccounts()->findOrFail($accountId);
        $deletedAccountId = $account->id;

        $account->delete();

        if ($this->editingAccountId === $deletedAccountId) {
            $this->resetAccountForm();
        }

        if ($this->passwordAccountId === $deletedAccountId) {
            $this->cancelPasswordChange();
        }

        session()->flash('account_status', 'Account deleted successfully.');
    }

    public function startPasswordChange(int $accountId): void
    {
        $account = $this->managedAccounts()->findOrFail($accountId);

        $this->passwordAccountId = $account->id;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->resetValidation(['newPassword', 'newPasswordConfirmation']);
    }

    public function updateAccountPassword(): void
    {
        $validated = $this->validate([
            'passwordAccountId' => ['required', 'integer'],
            'newPassword' => ['required', 'string', 'min:8', 'max:255'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ]);

        $account = $this->managedAccounts()->findOrFail((int) $validated['passwordAccountId']);
        $account->forceFill([
            'password' => Hash::make($validated['newPassword']),
        ])->save();

        $accountEmail = $account->email;
        $this->cancelPasswordChange();
        session()->flash('account_status', "Password updated for {$accountEmail}.");
    }

    public function cancelPasswordChange(): void
    {
        $this->passwordAccountId = null;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->resetValidation(['newPassword', 'newPasswordConfirmation']);
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
        return route('home', ['tenant' => $tenant->slug], absolute: true);
    }

    public function render()
    {
        $accounts = $this->managedAccounts()
            ->with('tenant:id,name')
            ->orderBy('name')
            ->get();

        return view('livewire.super.tenants.index', [
            'tenants' => Tenant::query()
                ->withCount(['users', 'units'])
                ->orderByDesc('created_at')
                ->get(),
            'accounts' => $accounts,
            'tenantOptions' => Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    private function resetAccountForm(): void
    {
        $this->editingAccountId = null;
        $this->accountTenantId = $this->defaultTenantId();
        $this->accountName = '';
        $this->accountEmail = '';
        $this->accountIsAdmin = true;
        $this->accountPassword = '';
        $this->accountPasswordConfirmation = '';
        $this->resetValidation([
            'accountTenantId',
            'accountName',
            'accountEmail',
            'accountIsAdmin',
            'accountPassword',
            'accountPasswordConfirmation',
        ]);
    }

    private function defaultTenantId(): ?int
    {
        $tenantId = Tenant::query()->orderBy('name')->value('id');

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }

    private function managedAccounts(): Builder
    {
        return User::query()->where('is_super_admin', false);
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
