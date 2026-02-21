<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <header class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Super Admin</p>
                    <h1 class="text-xl font-semibold text-slate-900">Tenant &amp; Account Management</h1>
                </div>
                <form method="POST" action="{{ route('super.logout') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Log Out
                    </button>
                </form>
            </header>

            <main>
                {{ $slot }}
            </main>
        </div>

        @fluxScripts
    </body>
</html>
