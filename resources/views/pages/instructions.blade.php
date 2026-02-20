<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $brandName = \App\Models\AppSetting::get('site_name', config('app.name', 'Condo Showroom')) ?? config('app.name', 'Condo Showroom');
    @endphp
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:py-12">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $brandName }} Instructions</h1>
                <div class="flex items-center gap-2">
                    <a href="{{ route('landing') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                        Back to Landing
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        Go to /login
                    </a>
                </div>
            </header>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Step 1</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-900">Public Sharing</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Copy your showroom link from the tenant dashboard and share it to clients. Shared links open the showroom directly, not the landing page.
                    </p>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Step 2</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-900">Tenant Login</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Tenant admins should always sign in by typing <span class="font-semibold text-slate-800">/login</span> in the browser address bar.
                    </p>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Step 3</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-900">Manage Showroom Data</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        After login, use the admin dashboard to manage units, categories, viewing requests, and rentals for your own tenant only.
                    </p>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Step 4</p>
                    <h2 class="mt-2 text-lg font-semibold text-slate-900">Password Safety</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Newly created tenant admin accounts start with default password <span class="font-semibold text-slate-800">12345678</span>. Change it immediately after first login in Settings.
                    </p>
                </section>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
