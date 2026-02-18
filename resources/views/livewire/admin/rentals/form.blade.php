<div class="space-y-7">
    <div class="flex flex-col gap-2">
        <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Create Rental</h2>
        <p class="text-sm text-slate-500">Generate secure renter access for a rental period.</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="rental-unit" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Unit (optional)</label>
                <select id="rental-unit" wire:model="unit_id" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="">Select unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unit_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="rental-renter-name" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Renter Name</label>
                <input id="rental-renter-name" type="text" wire:model="renter_name" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('renter_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="rental-contact-number" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Contact Number (optional)</label>
                <input id="rental-contact-number" type="text" wire:model="contact_number" placeholder="+63 912 345 6789" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('contact_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="rental-id-type" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">ID Type</label>
                <select id="rental-id-type" wire:model="id_type" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    @foreach($idTypeOptions as $option)
                        <option value="{{ $option }}">{{ str_replace('_', ' ', $option) }}</option>
                    @endforeach
                </select>
                @error('id_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="rental-id-last4" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">ID Last 4 (optional)</label>
                <input id="rental-id-last4" type="text" wire:model="id_last4" maxlength="4" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('id_last4') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="rental-starts-at" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Starts At</label>
                <input id="rental-starts-at" type="datetime-local" wire:model="starts_at" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('starts_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="rental-ends-at" class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-slate-500">Ends At</label>
                <input id="rental-ends-at" type="datetime-local" wire:model="ends_at" required class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                @error('ends_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <p class="text-sm text-slate-500">A secure rental code will be generated and shown once after save.</p>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.rentals.index') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Cancel</a>
            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-indigo-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-50">Create Rental</button>
        </div>
    </form>
</div>
