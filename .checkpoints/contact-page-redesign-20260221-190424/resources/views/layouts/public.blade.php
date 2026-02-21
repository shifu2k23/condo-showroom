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
        $showroomAppearance = \App\Models\AppSetting::get('showroom_appearance', 'light') ?? 'light';
        if (! in_array($showroomAppearance, ['light', 'dark'], true)) {
            $showroomAppearance = 'light';
        }
        $showroomAppearanceClass = $showroomAppearance === 'dark' ? 'showroom-theme-dark dark' : 'showroom-theme-light';
        $isShowroomRoute = request()->routeIs('home');
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

            .showroom-entrance-shell {
                isolation: isolate;
            }

            .showroom-entrance-overlay {
                position: fixed;
                inset: 0;
                z-index: 90;
                pointer-events: none;
                background:
                    radial-gradient(circle at 10% 20%, rgba(20, 184, 166, 0.30) 0%, rgba(15, 23, 42, 0) 46%),
                    radial-gradient(circle at 84% 18%, rgba(244, 114, 182, 0.25) 0%, rgba(15, 23, 42, 0) 42%),
                    radial-gradient(circle at 40% 84%, rgba(245, 158, 11, 0.22) 0%, rgba(15, 23, 42, 0) 45%),
                    linear-gradient(125deg, #020617 0%, #0f172a 46%, #111827 100%);
                opacity: 1;
                transform-origin: top;
            }

            .showroom-entrance-overlay::before,
            .showroom-entrance-overlay::after {
                content: '';
                position: absolute;
                border-radius: 999px;
                filter: blur(36px);
            }

            .showroom-entrance-overlay::before {
                height: 14rem;
                width: 14rem;
                background: rgba(45, 212, 191, 0.35);
                top: 8%;
                left: 10%;
            }

            .showroom-entrance-overlay::after {
                height: 18rem;
                width: 18rem;
                background: rgba(251, 146, 60, 0.26);
                right: -4%;
                bottom: 4%;
            }

            .showroom-entrance-mark {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%) scale(0.9);
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                border: 1px solid rgba(244, 114, 182, 0.36);
                border-radius: 999px;
                padding: 0.68rem 1.35rem;
                color: #e2e8f0;
                background: rgba(15, 23, 42, 0.55);
                backdrop-filter: blur(6px);
                letter-spacing: 0.18em;
                font-size: 0.67rem;
                font-weight: 700;
                text-transform: uppercase;
                opacity: 0;
            }

            .showroom-entrance-mark::before {
                content: '';
                width: 0.55rem;
                height: 0.55rem;
                border-radius: 999px;
                background: linear-gradient(135deg, #2dd4bf 0%, #f472b6 100%);
                box-shadow: 0 0 24px rgba(45, 212, 191, 0.8);
            }

            .showroom-entrance-overlay.is-active {
                animation: showroom-overlay-curtain 1.35s cubic-bezier(0.2, 0.88, 0.22, 1) forwards;
            }

            .showroom-entrance-overlay.is-active .showroom-entrance-mark {
                animation: showroom-mark-pop 0.8s 0.08s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            }

            .showroom-entrance-content {
                opacity: 0;
                transform: translateY(24px) scale(0.986);
                filter: blur(8px);
            }

            .showroom-ready .showroom-entrance-content {
                animation: showroom-content-rise 0.96s 0.36s cubic-bezier(0.18, 0.88, 0.22, 1) forwards;
            }

            .showroom-nav-shell {
                transition: background-color 360ms ease, border-color 360ms ease;
            }

            .showroom-stage-heading,
            .showroom-stage-controls,
            .showroom-stage-filters,
            .showroom-card-reveal {
                opacity: 0;
                transform: translateY(24px);
                filter: blur(5px);
            }

            .showroom-ready .showroom-stage-heading {
                animation: showroom-item-rise 0.72s 0.5s cubic-bezier(0.2, 0.88, 0.22, 1) forwards;
            }

            .showroom-ready .showroom-stage-controls {
                animation: showroom-item-rise 0.72s 0.62s cubic-bezier(0.2, 0.88, 0.22, 1) forwards;
            }

            .showroom-ready .showroom-stage-filters {
                animation: showroom-item-rise 0.72s 0.74s cubic-bezier(0.2, 0.88, 0.22, 1) forwards;
            }

            .showroom-ready .showroom-card-reveal {
                animation: showroom-card-rise 0.72s calc(0.74s + (var(--reveal-index, 0) * 70ms)) cubic-bezier(0.2, 0.88, 0.22, 1) forwards;
            }

            .showroom-ready .showroom-card-reveal img {
                animation: showroom-image-focus 0.96s calc(0.82s + (var(--reveal-index, 0) * 70ms)) ease forwards;
            }

            @keyframes showroom-overlay-curtain {
                0% {
                    opacity: 1;
                    transform: scaleY(1);
                }
                70% {
                    opacity: 1;
                    transform: scaleY(1.02);
                }
                100% {
                    opacity: 0;
                    transform: scaleY(0);
                }
            }

            @keyframes showroom-mark-pop {
                0% {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.9);
                }
                35% {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
                100% {
                    opacity: 0;
                    transform: translate(-50%, -55%) scale(1.04);
                }
            }

            @keyframes showroom-content-rise {
                0% {
                    opacity: 0;
                    transform: translateY(24px) scale(0.986);
                    filter: blur(8px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                    filter: blur(0);
                }
            }

            @keyframes showroom-item-rise {
                0% {
                    opacity: 0;
                    transform: translateY(22px);
                    filter: blur(5px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0);
                    filter: blur(0);
                }
            }

            @keyframes showroom-card-rise {
                0% {
                    opacity: 0;
                    transform: translateY(24px) scale(0.97);
                    filter: blur(5px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                    filter: blur(0);
                }
            }

            @keyframes showroom-image-focus {
                from {
                    filter: saturate(0.78) contrast(0.92) brightness(0.95);
                }
                to {
                    filter: saturate(1) contrast(1) brightness(1);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .showroom-entrance-overlay {
                    display: none;
                }

                .showroom-entrance-content,
                .showroom-stage-heading,
                .showroom-stage-controls,
                .showroom-stage-filters,
                .showroom-card-reveal {
                    opacity: 1 !important;
                    transform: none !important;
                    filter: none !important;
                    animation: none !important;
                }
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100 [font-family:'Manrope',sans-serif] {{ $showroomAppearanceClass }} {{ $isShowroomRoute ? 'showroom-body' : '' }}">
        <div class="relative min-h-screen bg-[radial-gradient(circle_at_10%_0%,rgba(99,102,241,0.16)_0%,rgba(248,250,252,0)_35%),radial-gradient(circle_at_90%_10%,rgba(14,165,233,0.12)_0%,rgba(248,250,252,0)_40%)] dark:bg-[radial-gradient(circle_at_10%_0%,rgba(99,102,241,0.22)_0%,rgba(2,6,23,0)_40%),radial-gradient(circle_at_90%_10%,rgba(14,165,233,0.18)_0%,rgba(2,6,23,0)_45%)] {{ $isShowroomRoute ? 'showroom-entrance-shell' : '' }}">
            @if ($isShowroomRoute)
                <div class="showroom-entrance-overlay" data-showroom-entrance="overlay" aria-hidden="true">
                    <div class="showroom-entrance-mark">Curated Showroom</div>
                </div>
            @endif

            <nav class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/80 backdrop-blur-md dark:border-slate-800/80 dark:bg-slate-900/80 {{ $isShowroomRoute ? 'showroom-nav-shell' : '' }}">
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

                        <div class="hidden items-center gap-8 text-sm font-medium text-slate-600 dark:text-slate-300 md:flex" aria-label="Primary">
                            <a href="{{ route('home') }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-300">Showrooms</a>
                            <a href="{{ route('home') }}#category-filters" class="transition hover:text-indigo-600 dark:hover:text-indigo-300">Categories</a>
                            <a href="#contact" class="transition hover:text-indigo-600 dark:hover:text-indigo-300">Contact</a>
                        </div>

                        <a
                            href="{{ route('renter.access') }}"
                            class="inline-flex min-h-11 items-center justify-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-900/40 dark:bg-indigo-500 dark:text-slate-950 dark:hover:bg-indigo-400 dark:focus-visible:ring-indigo-500/40 sm:px-5"
                        >
                            Renter Access
                        </a>
                    </div>

                    <div class="flex items-center gap-4 pb-3 text-sm font-medium text-slate-600 dark:text-slate-300 md:hidden" aria-label="Primary">
                        <a href="{{ route('home') }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-300">Showrooms</a>
                        <a href="{{ route('home') }}#category-filters" class="transition hover:text-indigo-600 dark:hover:text-indigo-300">Categories</a>
                        <a href="#contact" class="transition hover:text-indigo-600 dark:hover:text-indigo-300">Contact</a>
                    </div>
                </div>
            </nav>

            <main class="{{ $isShowroomRoute ? 'showroom-entrance-content' : '' }}">
                {{ $slot }}
            </main>

            <footer id="contact" class="mt-16 border-t border-slate-200 bg-white/70 dark:border-slate-800 dark:bg-slate-900/70">
                <div class="mx-auto max-w-7xl px-6 py-8">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Contact</h2>
                    <div class="mt-4 grid gap-2 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2 lg:grid-cols-3">
                        <p><span class="font-medium text-slate-800 dark:text-slate-100">Contact Number:</span> {{ $contactNumber !== '' ? $contactNumber : 'Not set' }}</p>
                        @if ($contactFacebook !== '')
                            <p><span class="font-medium text-slate-800 dark:text-slate-100">Facebook:</span> {{ $contactFacebook }}</p>
                        @endif
                        @if ($contactGmail !== '')
                            <p><span class="font-medium text-slate-800 dark:text-slate-100">Gmail:</span> <a href="mailto:{{ $contactGmail }}" class="text-indigo-600 hover:underline dark:text-indigo-300">{{ $contactGmail }}</a></p>
                        @endif
                        @if ($contactInstagram !== '')
                            <p><span class="font-medium text-slate-800 dark:text-slate-100">Instagram:</span> {{ $contactInstagram }}</p>
                        @endif
                        @if ($contactViber !== '')
                            <p><span class="font-medium text-slate-800 dark:text-slate-100">Viber:</span> {{ $contactViber }}</p>
                        @endif
                        @if ($contactTelegram !== '')
                            <p><span class="font-medium text-slate-800 dark:text-slate-100">Telegram:</span> {{ $contactTelegram }}</p>
                        @endif
                    </div>

                    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">
                        &copy; {{ date('Y') }} {{ $brandName }}.
                    </p>
                </div>
            </footer>
        </div>

        @fluxScripts

        @if ($isShowroomRoute)
            <script>
                (() => {
                    const body = document.body;
                    const overlay = document.querySelector('[data-showroom-entrance="overlay"]');
                    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                    if (reduceMotion || !overlay) {
                        body.classList.add('showroom-ready');
                        if (overlay) {
                            overlay.remove();
                        }
                        return;
                    }

                    requestAnimationFrame(() => {
                        overlay.classList.add('is-active');
                        body.classList.add('showroom-ready');
                    });

                    window.setTimeout(() => {
                        overlay.remove();
                    }, 1800);
                })();
            </script>
        @endif
    </body>
</html>
