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
