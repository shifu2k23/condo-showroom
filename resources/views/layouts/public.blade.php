<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $brandName }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <style>
            .scrollbar-hide::-webkit-scrollbar { display: none; }
            .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased [font-family:'Manrope',sans-serif]">
        <div class="relative min-h-screen bg-[radial-gradient(circle_at_10%_0%,rgba(99,102,241,0.16)_0%,rgba(248,250,252,0)_35%),radial-gradient(circle_at_90%_10%,rgba(14,165,233,0.12)_0%,rgba(248,250,252,0)_40%)]">
            <nav class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur-md">
                <div class="mx-auto max-w-7xl px-4 sm:px-6">
                    <div class="flex min-h-20 items-center justify-between gap-3 py-3">
                        <a href="{{ route('home') }}" class="flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40">
                            @if ($brandLogoUrl)
                                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-8 w-8 shrink-0 rounded-lg object-cover" />
                            @else
                                <span class="h-8 w-8 shrink-0 rounded-lg bg-indigo-600" aria-hidden="true"></span>
                            @endif
                            <span class="text-lg font-bold tracking-tight sm:text-xl">{{ $brandName }}</span>
                        </a>

                        <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex" aria-label="Primary">
                            <a href="{{ route('home') }}" class="transition hover:text-indigo-600">Showrooms</a>
                            <a href="{{ route('home') }}#category-filters" class="transition hover:text-indigo-600">Categories</a>
                            <a href="#contact" class="transition hover:text-indigo-600">Contact</a>
                        </div>

                        <a
                            href="{{ route('renter.access') }}"
                            class="inline-flex min-h-11 items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900/40 sm:px-5"
                        >
                            Renter Access
                        </a>
                    </div>

                    <div class="flex items-center gap-4 pb-3 text-sm font-medium text-slate-600 md:hidden" aria-label="Primary">
                        <a href="{{ route('home') }}" class="transition hover:text-indigo-600">Showrooms</a>
                        <a href="{{ route('home') }}#category-filters" class="transition hover:text-indigo-600">Categories</a>
                        <a href="#contact" class="transition hover:text-indigo-600">Contact</a>
                    </div>
                </div>
            </nav>

            <main>
                {{ $slot }}
            </main>

            <footer id="contact" class="mt-16 border-t border-slate-200 bg-white/70">
                <div class="mx-auto max-w-7xl px-6 py-8">
                    <h2 class="text-base font-semibold text-slate-900">Contact</h2>
                    <div class="mt-4 grid gap-2 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-3">
                        <p><span class="font-medium text-slate-800">Contact Number:</span> {{ $contactNumber !== '' ? $contactNumber : 'Not set' }}</p>
                        @if ($contactFacebook !== '')
                            <p><span class="font-medium text-slate-800">Facebook:</span> {{ $contactFacebook }}</p>
                        @endif
                        @if ($contactGmail !== '')
                            <p><span class="font-medium text-slate-800">Gmail:</span> <a href="mailto:{{ $contactGmail }}" class="text-indigo-600 hover:underline">{{ $contactGmail }}</a></p>
                        @endif
                        @if ($contactInstagram !== '')
                            <p><span class="font-medium text-slate-800">Instagram:</span> {{ $contactInstagram }}</p>
                        @endif
                        @if ($contactViber !== '')
                            <p><span class="font-medium text-slate-800">Viber:</span> {{ $contactViber }}</p>
                        @endif
                        @if ($contactTelegram !== '')
                            <p><span class="font-medium text-slate-800">Telegram:</span> {{ $contactTelegram }}</p>
                        @endif
                    </div>

                    <p class="mt-6 text-center text-sm text-slate-500">
                        &copy; {{ date('Y') }} {{ $brandName }}.
                    </p>
                </div>
            </footer>
        </div>

        @fluxScripts
    </body>
</html>
