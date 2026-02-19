<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <section class="px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/60 sm:p-8">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Tenant Login</h1>
                    <p class="mt-2 text-sm text-slate-500">Enter your tenant slug to continue to your tenant login page.</p>

                    <form method="POST" action="{{ route('tenant.login.redirect') }}" class="mt-6 space-y-4">
                        @csrf

                        <div>
                            <label for="tenant_slug" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant Slug</label>
                            <input
                                id="tenant_slug"
                                name="tenant_slug"
                                type="text"
                                value="{{ old('tenant_slug') }}"
                                autocomplete="off"
                                placeholder="acme"
                                class="h-11 w-full rounded-xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                                required
                            >
                            @error('tenant_slug')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                            Continue
                        </button>
                    </form>
                </div>
            </div>
        </section>

        @fluxScripts
    </body>
</html>
