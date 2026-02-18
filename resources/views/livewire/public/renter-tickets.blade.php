<div class="py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">Maintenance Tickets</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Submit and track concerns for your active rental unit.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                <a
                    href="{{ route('renter.dashboard') }}"
                    class="inline-flex w-full items-center justify-center rounded-md border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 sm:w-auto"
                >
                    Back to Dashboard
                </a>
                <button
                    type="button"
                    wire:click="logout"
                    class="inline-flex w-full items-center justify-center rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 sm:w-auto"
                >
                    Sign Out
                </button>
            </div>
        </div>

        @if($statusMessage)
            <div class="mb-4 rounded-md bg-zinc-100 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                {{ $statusMessage }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 lg:col-span-1 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Create Ticket</h2>

                @if($this->isAccessExpired())
                    <div class="mt-4 rounded-md bg-zinc-100 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        {{ $this->expiredTicketMessage() }}
                    </div>
                @endif

                <form wire:submit.prevent="submit" class="mt-4 space-y-3">
                    <flux:select wire:model="category" label="Category" required>
                        @foreach($categoryOptions as $option)
                            <option value="{{ $option }}">{{ str_replace('_', ' ', $option) }}</option>
                        @endforeach
                    </flux:select>
                    @error('category') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <flux:input wire:model="subject" label="Subject" required />
                    @error('subject') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <flux:textarea wire:model="description" label="Description" rows="5" required />
                    @error('description') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <div>
                        <label class="mb-2 block text-xs font-medium uppercase tracking-[0.14em] text-zinc-500">Attachment (optional)</label>
                        <input type="file" wire:model="attachment" accept="image/*" class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200 dark:text-zinc-300 dark:file:bg-zinc-800 dark:file:text-zinc-200">
                        @error('attachment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-md bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50"
                        @if($this->isAccessExpired()) disabled @endif
                    >
                        Submit Ticket
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Your Tickets</h2>

                @if($tickets->isEmpty())
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">No tickets submitted yet.</p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach($tickets as $ticket)
                            <article class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $ticket->subject }}</h3>
                                    <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ str_replace('_', ' ', $ticket->status) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs uppercase tracking-wide text-zinc-500">{{ str_replace('_', ' ', $ticket->category) }}</p>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $ticket->description }}</p>
                                @php($attachmentUrl = $this->attachmentUrl($ticket->attachment_path))
                                @if($attachmentUrl)
                                    <a
                                        href="{{ $attachmentUrl }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="mt-3 inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-500"
                                    >
                                        View Attachment
                                    </a>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>
