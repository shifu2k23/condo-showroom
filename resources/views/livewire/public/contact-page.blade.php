@php
    $brandName = \App\Models\AppSetting::get('site_name', config('app.name', 'Condo Showroom')) ?? config('app.name', 'Condo Showroom');
    $brandLogoPath = \App\Models\AppSetting::get('site_logo_path');
    $brandLogoUrl = $brandLogoPath ? \Illuminate\Support\Facades\Storage::url($brandLogoPath) : null;
    $contactNumber = \App\Models\AppSetting::get('contact_number', '') ?? '';
    $contactFacebook = \App\Models\AppSetting::get('contact_facebook', '') ?? '';
    $contactGmail = \App\Models\AppSetting::get('contact_gmail', '') ?? '';
    $contactInstagram = \App\Models\AppSetting::get('contact_instagram', '') ?? '';
    $contactViber = \App\Models\AppSetting::get('contact_viber', '') ?? '';
    $contactTelegram = \App\Models\AppSetting::get('contact_telegram', '') ?? '';
@endphp

<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16">
    <div class="mb-6">
        <a
            href="{{ route('home') }}"
            class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-300"
        >
            <span aria-hidden="true">&larr;</span>
            Back to showroom
        </a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white/85 shadow-[0_28px_100px_-40px_rgba(15,23,42,0.45)] backdrop-blur dark:border-slate-700/80 dark:bg-slate-900/80">
        <div class="grid grid-cols-1 gap-0 lg:grid-cols-2">
            <div class="relative overflow-hidden border-b border-slate-200 p-8 sm:p-10 lg:border-b-0 lg:border-r dark:border-slate-700">
                <div aria-hidden="true" class="pointer-events-none absolute -left-14 -top-14 h-52 w-52 rounded-full bg-indigo-300/40 blur-3xl dark:bg-indigo-500/20"></div>
                <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 -right-16 h-56 w-56 rounded-full bg-teal-300/35 blur-3xl dark:bg-teal-500/20"></div>

                <div class="relative">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Contact</p>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                        {{ $brandName }}
                    </h1>
                    <p class="mt-3 max-w-md text-sm text-slate-600 sm:text-base dark:text-slate-300">
                        Reach us directly for bookings, showroom questions, and rental inquiries.
                    </p>

                    <div class="mt-8 inline-flex rounded-3xl bg-slate-100/90 p-4 shadow-inner dark:bg-slate-800/70">
                        @if ($brandLogoUrl)
                            <img
                                src="{{ $brandLogoUrl }}"
                                alt="{{ $brandName }} logo"
                                class="h-28 w-28 rounded-2xl object-cover sm:h-36 sm:w-36"
                            />
                        @else
                            <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-indigo-600 text-2xl font-black text-white sm:h-36 sm:w-36 sm:text-3xl">
                                {{ \Illuminate\Support\Str::of($brandName)->trim()->substr(0, 2)->upper() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-8 sm:p-10">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">Credentials</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Official contact channels</p>

                <div class="mt-6 space-y-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Contact Number</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $contactNumber !== '' ? $contactNumber : 'Not set' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Facebook</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $contactFacebook !== '' ? $contactFacebook : 'Not set' }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Gmail</p>
                        @if ($contactGmail !== '')
                            <a href="mailto:{{ $contactGmail }}" class="mt-1 inline-block text-sm font-semibold text-indigo-700 hover:underline dark:text-indigo-300">{{ $contactGmail }}</a>
                        @else
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">Not set</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Instagram</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $contactInstagram !== '' ? $contactInstagram : 'Not set' }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Viber</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $contactViber !== '' ? $contactViber : 'Not set' }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/70">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Telegram</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $contactTelegram !== '' ? $contactTelegram : 'Not set' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

