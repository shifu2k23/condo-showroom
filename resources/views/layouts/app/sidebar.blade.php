<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    @php
        $pageTitle = match (true) {
            request()->routeIs('admin.dashboard') => 'Dashboard',
            request()->routeIs('admin.units.*') => 'Units',
            request()->routeIs('admin.categories.*') => 'Categories',
            request()->routeIs('admin.viewing-requests.*') => 'Viewing Requests',
            request()->routeIs('admin.rentals.*') => 'Rentals',
            request()->routeIs('admin.logs.*') => 'Audit Logs',
            default => 'Admin',
        };
        $menuItems = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'home'],
            ['label' => 'Units', 'route' => 'admin.units.index', 'active' => request()->routeIs('admin.units.*'), 'icon' => 'building'],
            ['label' => 'Categories', 'route' => 'admin.categories.index', 'active' => request()->routeIs('admin.categories.*'), 'icon' => 'layers'],
            ['label' => 'Viewing Requests', 'route' => 'admin.viewing-requests.index', 'active' => request()->routeIs('admin.viewing-requests.*'), 'icon' => 'calendar'],
            ['label' => 'Rentals', 'route' => 'admin.rentals.index', 'active' => request()->routeIs('admin.rentals.*'), 'icon' => 'key'],
            ['label' => 'Audit Logs', 'route' => 'admin.logs.index', 'active' => request()->routeIs('admin.logs.*'), 'icon' => 'clipboard'],
        ];
    @endphp
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top,rgba(79,70,229,0.08),transparent_55%)]"></div>

        <div class="relative min-h-screen lg:flex">
            <aside class="w-full border-b border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-64 lg:border-b-0 lg:border-r">
                <div class="flex h-20 items-center border-b border-slate-200 px-6">
                    <a href="{{ route('admin.dashboard') }}" wire:navigate class="inline-flex items-center gap-3 rounded-xl px-2 py-1.5 text-sm font-semibold text-indigo-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">SA</span>
                        <span>Showroom Admin</span>
                    </a>
                </div>

                <nav class="px-3 pb-3 pt-4 lg:pb-6" aria-label="Admin navigation">
                    <ul class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible">
                        @foreach ($menuItems as $item)
                            <li class="min-w-fit">
                                <a
                                    href="{{ route($item['route']) }}"
                                    wire:navigate
                                    @class([
                                        'group flex min-h-11 items-center gap-3 rounded-xl border-l-2 px-4 py-2.5 text-sm font-medium transition duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white',
                                        'border-indigo-600 bg-indigo-50 pl-3.5 font-semibold text-indigo-700' => $item['active'],
                                        'border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $item['active'],
                                    ])
                                    @if ($item['active']) aria-current="page" @endif
                                >
                                    @if ($item['icon'] === 'home')
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 15 19.5V15a1.5 1.5 0 0 0-1.5-1.5h-3A1.5 1.5 0 0 0 9 15v4.5A1.5 1.5 0 0 1 7.5 21h-3A1.5 1.5 0 0 1 3 19.5v-9Z"/></svg>
                                    @elseif ($item['icon'] === 'building')
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4.5 21V6.75A1.75 1.75 0 0 1 6.25 5h6.5A1.75 1.75 0 0 1 14.5 6.75V21M9 9h1m-1 3h1m-1 3h1m5-6h1m-1 3h1m-1 3h1M3 21h18"/></svg>
                                    @elseif ($item['icon'] === 'layers')
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 9 4.5-9 4.5-9-4.5L12 3Zm9 9-9 4.5-9-4.5m18 4.5-9 4.5-9-4.5"/></svg>
                                    @elseif ($item['icon'] === 'calendar')
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7.5 3v3m9-3v3M4.5 9h15M6.25 5.5h11.5A1.75 1.75 0 0 1 19.5 7.25v10.5a1.75 1.75 0 0 1-1.75 1.75H6.25a1.75 1.75 0 0 1-1.75-1.75V7.25A1.75 1.75 0 0 1 6.25 5.5Z"/></svg>
                                    @elseif ($item['icon'] === 'key')
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14.5 6a4.5 4.5 0 1 1-3.65 7.13L3 21h4.5v-2.25h2.25V16.5H12l1.1-1.1A4.5 4.5 0 0 1 14.5 6Z"/></svg>
                                    @else
                                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M9 5.25h9a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H6a1.5 1.5 0 0 1-1.5-1.5v-9m4.5-4.5v4.5h-4.5m4.5-4.5L15 11.25"/></svg>
                                    @endif
                                    <span class="whitespace-nowrap">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="hidden border-t border-slate-200 px-4 py-4 lg:mt-auto lg:block">
                    <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-100 text-sm font-semibold text-indigo-700">{{ auth()->user()->initials() }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                            Log Out
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex-1 lg:pl-64">
                <header class="sticky top-0 z-20 border-b border-slate-200/90 bg-white/80 backdrop-blur">
                    <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                        <div class="flex items-center gap-2">
                            <span class="text-xs uppercase tracking-[0.18em] text-slate-500">Admin Panel</span>
                            <span class="text-slate-300">/</span>
                            <h1 class="text-lg font-semibold text-slate-900">{{ $pageTitle }}</h1>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="relative w-full sm:w-72">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                                <input type="search" placeholder="Search units by name" aria-label="Global search units (UI only)" class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                            </div>
                            <button type="button" aria-label="Notifications" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.42V11a6 6 0 1 0-12 0v3.18a2 2 0 0 1-.59 1.41L4 17h5m6 0a3 3 0 1 1-6 0m6 0H9"/></svg>
                            </button>
                            <button type="button" aria-label="User menu" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:-translate-y-0.5 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-white">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">{{ auth()->user()->initials() }}</span>
                                <span class="hidden sm:inline">Account</span>
                                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.12l3.71-3.9a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </header>

                <main class="relative">
                    <div class="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <div id="admin-confirm-overlay" class="pointer-events-none fixed inset-0 z-[90] flex items-end justify-center p-4 opacity-0 transition-opacity duration-200 sm:items-center sm:p-6" aria-hidden="true">
            <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]" data-confirm-backdrop></div>
            <div id="admin-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title" aria-describedby="admin-confirm-message" class="relative w-full max-w-md translate-y-6 scale-[0.96] rounded-3xl border border-slate-700/70 bg-gradient-to-b from-slate-900 to-slate-950 p-6 text-slate-100 opacity-0 shadow-[0_18px_60px_rgba(2,6,23,0.55)] transition-all duration-300 ease-out">
                <div class="mb-4 flex items-center gap-3">
                    <span id="admin-confirm-icon" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-300/30 bg-rose-400/10 text-rose-200">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 9v4m0 4h.01M10.25 3.76 1.82 18a1.5 1.5 0 0 0 1.29 2.25h16.78A1.5 1.5 0 0 0 21.18 18L12.75 3.76a1.5 1.5 0 0 0-2.5 0Z"/>
                        </svg>
                    </span>
                    <p id="admin-confirm-title" class="text-lg font-semibold tracking-tight">Confirm Action</p>
                </div>

                <p id="admin-confirm-message" class="text-sm leading-relaxed text-slate-300">
                    This action needs your confirmation.
                </p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button id="admin-confirm-cancel" type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-600 bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:-translate-y-0.5 hover:bg-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                        Keep Editing
                    </button>
                    <button id="admin-confirm-approve" type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-rose-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/40 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                        Yes, Continue
                    </button>
                </div>
            </div>
        </div>

        @fluxScripts
        <script>
            (() => {
                const overlay = document.getElementById('admin-confirm-overlay');
                const dialog = document.getElementById('admin-confirm-dialog');
                const titleEl = document.getElementById('admin-confirm-title');
                const messageEl = document.getElementById('admin-confirm-message');
                const iconWrap = document.getElementById('admin-confirm-icon');
                const confirmButton = document.getElementById('admin-confirm-approve');
                const cancelButton = document.getElementById('admin-confirm-cancel');

                if (!overlay || !dialog || !titleEl || !messageEl || !confirmButton || !cancelButton || !iconWrap) {
                    return;
                }

                const neutralButtonClasses = ['bg-indigo-600', 'hover:bg-indigo-500', 'focus-visible:ring-indigo-500/40'];
                const dangerButtonClasses = ['bg-rose-600', 'hover:bg-rose-500', 'focus-visible:ring-rose-500/40'];
                const neutralIconClasses = ['border-indigo-300/30', 'bg-indigo-400/10', 'text-indigo-200'];
                const dangerIconClasses = ['border-rose-300/30', 'bg-rose-400/10', 'text-rose-200'];

                let pendingTrigger = null;
                let isOpen = false;

                const openModal = (trigger) => {
                    const title = trigger.getAttribute('data-confirm-title') || 'Confirm Action';
                    const message = trigger.getAttribute('data-confirm') || 'This action needs your confirmation.';
                    const approveLabel = trigger.getAttribute('data-confirm-confirm') || 'Yes, Continue';
                    const cancelLabel = trigger.getAttribute('data-confirm-cancel') || 'Keep Editing';
                    const tone = trigger.getAttribute('data-confirm-tone') || 'danger';

                    titleEl.textContent = title;
                    messageEl.textContent = message;
                    confirmButton.textContent = approveLabel;
                    cancelButton.textContent = cancelLabel;

                    confirmButton.classList.remove(...neutralButtonClasses, ...dangerButtonClasses);
                    iconWrap.classList.remove(...neutralIconClasses, ...dangerIconClasses);

                    if (tone === 'neutral') {
                        confirmButton.classList.add(...neutralButtonClasses);
                        iconWrap.classList.add(...neutralIconClasses);
                    } else {
                        confirmButton.classList.add(...dangerButtonClasses);
                        iconWrap.classList.add(...dangerIconClasses);
                    }

                    pendingTrigger = trigger;
                    isOpen = true;

                    overlay.classList.remove('pointer-events-none', 'opacity-0');
                    overlay.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('overflow-hidden');

                    requestAnimationFrame(() => {
                        dialog.classList.remove('translate-y-6', 'scale-[0.96]', 'opacity-0');
                    });
                };

                const closeModal = () => {
                    if (!isOpen) {
                        return;
                    }

                    isOpen = false;
                    pendingTrigger = null;
                    overlay.setAttribute('aria-hidden', 'true');
                    overlay.classList.add('opacity-0');
                    dialog.classList.add('translate-y-6', 'scale-[0.96]', 'opacity-0');
                    document.body.classList.remove('overflow-hidden');

                    window.setTimeout(() => {
                        if (!isOpen) {
                            overlay.classList.add('pointer-events-none');
                        }
                    }, 210);
                };

                document.addEventListener('click', (event) => {
                    const target = event.target;

                    if (!(target instanceof Element)) {
                        return;
                    }

                    const trigger = target.closest('[data-confirm]');

                    if (!trigger) {
                        return;
                    }

                    if (trigger.getAttribute('data-confirming') === 'true') {
                        trigger.removeAttribute('data-confirming');

                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();

                    openModal(trigger);
                }, true);

                confirmButton.addEventListener('click', () => {
                    const trigger = pendingTrigger;
                    closeModal();

                    if (!trigger) {
                        return;
                    }

                    trigger.setAttribute('data-confirming', 'true');
                    trigger.click();
                });

                cancelButton.addEventListener('click', closeModal);

                overlay.addEventListener('click', (event) => {
                    const target = event.target;

                    if (!(target instanceof Element)) {
                        return;
                    }

                    if (target.hasAttribute('data-confirm-backdrop')) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeModal();
                    }
                });
            })();
        </script>
    </body>
</html>
