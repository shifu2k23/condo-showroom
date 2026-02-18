<div class="py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Renter Dashboard</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">View your active rental details and manage maintenance concerns.</p>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('renter.tickets') }}"
                    class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800"
                >
                    Manage Tickets
                </a>
                <button
                    type="button"
                    wire:click="logout"
                    class="inline-flex items-center justify-center rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800"
                >
                    Sign Out
                </button>
            </div>
        </div>

        @if(! $rental)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                Unable to load your rental details. Please sign in again.
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <section class="rounded-2xl border border-zinc-200 bg-white p-6 md:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Rented Unit Details</h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Unit</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $rental->unit?->name ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Category / Location</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $rental->unit?->category?->name ?? 'N/A' }}{{ $rental->unit?->location ? ' - '.$rental->unit->location : '' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Rental Start</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ optional($rental->starts_at)->format('M d, Y h:i A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">Rental End</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ optional($rental->ends_at)->format('M d, Y h:i A') }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Status</h2>
                    <p class="mt-3">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $this->isExpired ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                            {{ $this->isExpired ? 'Expired' : 'Active' }}
                        </span>
                    </p>
                    @if($this->isExpired)
                        <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">
                            Your rental period has ended. Ticket submission is unavailable. Please contact the front desk if you need assistance.
                        </p>
                    @endif
                </section>
            </div>

            <section class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Recent Maintenance Tickets</h2>

                @if($recentTickets->isEmpty())
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">No maintenance tickets submitted yet.</p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach($recentTickets as $ticket)
                            <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $ticket->subject }}</h3>
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ str_replace('_', ' ', $ticket->status) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs uppercase tracking-wide text-zinc-500">{{ $ticket->category }}</p>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $ticket->description }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</div>
