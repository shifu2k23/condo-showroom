<div class="space-y-6">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Create Tenant</h2>

        @if(session('status'))
            <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if(session('generated_admin_credentials'))
            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <p class="font-semibold">Initial tenant admin credentials (shown once)</p>
                <p>Email: <span class="font-mono">{{ session('generated_admin_credentials.email') }}</span></p>
                <p>Password: <span class="font-mono">{{ session('generated_admin_credentials.password') }}</span></p>
                <p class="mt-1 text-xs text-amber-800">Default password is intentionally simple. Ask the tenant admin to change it immediately after first login via Settings -> Password.</p>
            </div>
        @endif

        <form wire:submit.prevent="createTenant" class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant Name</label>
                <input type="text" wire:model="name" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Trial Days</label>
                <input type="number" min="1" max="3650" wire:model="trialDays" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('trialDays') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <label class="mt-7 inline-flex min-h-11 items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model="createAdmin" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/40">
                <span>Create Initial Tenant Admin</span>
            </label>

            @if($createAdmin)
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Admin Name</label>
                    <input type="text" wire:model="adminName" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    @error('adminName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Admin Email</label>
                    <input type="email" wire:model="adminEmail" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    @error('adminEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="md:col-span-2">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    Create Tenant
                </button>
            </div>
        </form>
    </section>

    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-r from-indigo-50 via-sky-50 to-emerald-50"></div>

        <div class="relative p-5 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1">
                    <h2 class="text-lg font-semibold text-slate-900">Account Control Center</h2>
                    <p class="text-sm text-slate-500">Manage tenant user access, credentials, and account roles in one workspace.</p>
                </div>

                <div class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">
                    {{ $editingAccountId ? 'Edit Mode' : 'Create Mode' }}
                </div>
            </div>

            @if(session('account_status'))
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('account_status') }}
                </div>
            @endif

            @php($totalAccounts = $accounts->count())
            @php($tenantAdminCount = $accounts->where('is_admin', true)->count())
            @php($standardUserCount = $accounts->where('is_admin', false)->count())
            @php($coveredTenantCount = $accounts->pluck('tenant_id')->filter()->unique()->count())
            @php($passwordTarget = $accounts->firstWhere('id', $passwordAccountId))

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Total Accounts</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $totalAccounts }}</p>
                </div>
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-indigo-600">Tenant Admins</p>
                    <p class="mt-1 text-2xl font-semibold text-indigo-900">{{ $tenantAdminCount }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-600">Standard Users</p>
                    <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $standardUserCount }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-emerald-600">Tenants Covered</p>
                    <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $coveredTenantCount }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 xl:grid-cols-12">
                <div class="xl:col-span-5">
                    <div class="h-full rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">Account Studio</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">
                                {{ $editingAccountId ? 'Updating' : 'New Account' }}
                            </span>
                        </div>

                        <form wire:submit.prevent="saveAccount" class="space-y-4">
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</label>
                                <select wire:model="accountTenantId" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                    <option value="">Select Tenant</option>
                                    @foreach($tenantOptions as $tenantOption)
                                        <option value="{{ $tenantOption->id }}">{{ $tenantOption->name }}</option>
                                    @endforeach
                                </select>
                                @error('accountTenantId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Full Name</label>
                                <input type="text" wire:model="accountName" autocomplete="name" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                @error('accountName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                                <input type="email" wire:model="accountEmail" autocomplete="email" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                @error('accountEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $editingAccountId ? 'New Password (optional)' : 'Password' }}
                                </label>
                                <input type="password" wire:model="accountPassword" autocomplete="new-password" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                @error('accountPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    {{ $editingAccountId ? 'Confirm New Password' : 'Confirm Password' }}
                                </label>
                                <input type="password" wire:model="accountPasswordConfirmation" autocomplete="new-password" class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                @error('accountPasswordConfirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <label class="inline-flex min-h-11 w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                <span>Tenant Admin Access</span>
                                <input type="checkbox" wire:model="accountIsAdmin" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/40">
                            </label>
                            @error('accountIsAdmin') <p class="-mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                    {{ $editingAccountId ? 'Update Account' : 'Create Account' }}
                                </button>
                                @if($editingAccountId)
                                    <button type="button" wire:click="cancelAccountEdit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                                        Cancel Edit
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>

                <div class="xl:col-span-7">
                    @if($passwordAccountId && $passwordTarget)
                        <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 sm:p-5">
                            <div class="flex flex-col gap-1">
                                <h3 class="text-sm font-semibold text-amber-900">Password Reset Panel</h3>
                                <p class="text-xs text-amber-800">
                                    You are updating password for <span class="font-semibold">{{ $passwordTarget->name }}</span> ({{ $passwordTarget->email }}).
                                </p>
                            </div>

                            <form wire:submit.prevent="updateAccountPassword" class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-amber-900">New Password</label>
                                    <input type="password" wire:model="newPassword" autocomplete="new-password" class="h-11 w-full rounded-xl border border-amber-200 px-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                                    @error('newPassword') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-amber-900">Confirm New Password</label>
                                    <input type="password" wire:model="newPasswordConfirmation" autocomplete="new-password" class="h-11 w-full rounded-xl border border-amber-200 px-3 text-sm text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                                    @error('newPasswordConfirmation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500">
                                            Update Password
                                        </button>
                                        <button type="button" wire:click="cancelPasswordChange" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-amber-200 bg-white px-5 py-2.5 text-sm font-medium text-amber-900 transition hover:bg-amber-100/60">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">Account Directory</h3>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-600">{{ $totalAccounts }} records</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($accounts as $account)
                                @php($isEditingAccount = $editingAccountId === $account->id)
                                @php($isPasswordFocused = $passwordAccountId === $account->id)

                                <article class="rounded-2xl border p-4 transition {{ $isEditingAccount ? 'border-indigo-300 bg-indigo-50/70' : ($isPasswordFocused ? 'border-amber-300 bg-amber-50/70' : 'border-slate-200 bg-white hover:border-slate-300') }}">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0 space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="truncate font-semibold text-slate-900">{{ $account->name }}</p>
                                                <span class="rounded-full px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.1em] {{ $account->is_admin ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700' }}">
                                                    {{ $account->is_admin ? 'Tenant Admin' : 'User' }}
                                                </span>
                                                @if($isEditingAccount)
                                                    <span class="rounded-full bg-indigo-200 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.1em] text-indigo-800">Editing</span>
                                                @endif
                                                @if($isPasswordFocused)
                                                    <span class="rounded-full bg-amber-200 px-2 py-1 text-[11px] font-semibold uppercase tracking-[0.1em] text-amber-800">Password Mode</span>
                                                @endif
                                            </div>

                                            <p class="truncate text-sm text-slate-600">{{ $account->email }}</p>
                                            <p class="text-xs text-slate-500">
                                                Tenant: <span class="font-medium text-slate-700">{{ $account->tenant?->name ?? 'N/A' }}</span>
                                                <span class="mx-1 text-slate-300">|</span>
                                                Created {{ $account->created_at?->toDateString() ?? 'N/A' }}
                                            </p>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" wire:click="editAccount({{ $account->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                                Edit
                                            </button>
                                            <button type="button" wire:click="startPasswordChange({{ $account->id }})" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">
                                                Change Password
                                            </button>
                                            <button type="button" wire:click="deleteAccount({{ $account->id }})" data-confirm-title="Delete Account" data-confirm="This permanently deletes {{ $account->email }}." data-confirm-confirm="Yes, Delete" data-confirm-cancel="Cancel" data-confirm-tone="danger" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                                    No accounts yet. Create one from the Account Studio panel.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Tenants</h2>
            <div class="flex items-center gap-2">
                <input type="number" min="1" max="365" wire:model="extendDays" class="h-10 w-24 rounded-lg border border-slate-200 px-2 text-sm text-slate-900">
                <span class="text-xs text-slate-500">days</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-2 py-2">Tenant</th>
                        <th class="px-2 py-2">Status</th>
                        <th class="px-2 py-2">Trial Ends</th>
                        <th class="px-2 py-2">Users</th>
                        <th class="px-2 py-2">Units</th>
                        <th class="px-2 py-2">Shareable Link</th>
                        <th class="px-2 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                    @forelse($tenants as $tenant)
                        <tr>
                            <td class="px-2 py-3">
                                <p class="font-semibold text-slate-900">{{ $tenant->name }}</p>
                            </td>
                            <td class="px-2 py-3">
                                @if($tenant->is_disabled)
                                    <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">Disabled</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Active</span>
                                @endif
                            </td>
                            <td class="px-2 py-3">{{ $tenant->trial_ends_at?->toDateString() ?? 'N/A' }}</td>
                            <td class="px-2 py-3">{{ $tenant->users_count }}</td>
                            <td class="px-2 py-3">{{ $tenant->units_count }}</td>
                            <td class="px-2 py-3">
                                <a href="{{ $this->shareableTenantUrl($tenant) }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">
                                    {{ $this->shareableTenantUrl($tenant) }}
                                </a>
                            </td>
                            <td class="px-2 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" wire:click="extendTrial({{ $tenant->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        Extend Trial
                                    </button>
                                    <button type="button" wire:click="toggleDisabled({{ $tenant->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                                        {{ $tenant->is_disabled ? 'Enable' : 'Disable' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-2 py-6 text-center text-sm text-slate-500">No tenants yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
