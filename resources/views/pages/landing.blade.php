<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $brandName = \App\Models\AppSetting::get('site_name', config('app.name', 'Condo Showroom')) ?? config('app.name', 'Condo Showroom');
    @endphp
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <div class="relative min-h-screen bg-[radial-gradient(circle_at_10%_0%,rgba(99,102,241,0.12)_0%,rgba(248,250,252,0)_40%),radial-gradient(circle_at_90%_0%,rgba(14,165,233,0.10)_0%,rgba(248,250,252,0)_35%)]">
            <header class="border-b border-slate-200/70 bg-white/80 backdrop-blur-md">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6">
                    <div class="text-lg font-bold tracking-tight text-slate-900">{{ $brandName }}</div>
                    <nav class="flex items-center gap-2 sm:gap-3">
                        <a href="{{ route('instructions') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            How To
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-500">
                            Login
                        </a>
                    </nav>
                </div>
            </header>

            <main class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:py-16">
                <section class="self-center">
                    <p class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-indigo-700">Condo Showroom Platform</p>
                    <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Showcase available units in one clean public showroom.</h1>
                    <p class="mt-4 max-w-xl text-base text-slate-600 sm:text-lg">
                        Clients can view your shared showroom link directly. Tenant admins can securely sign in only through <span class="font-semibold text-slate-800">/login</span> to manage units and requests.
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="{{ route('instructions') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            View Instructions
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                            Tenant Admin Login
                        </a>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50 sm:p-8">
                    <h2 class="text-xl font-bold text-slate-900">Tenant Login</h2>
                    <p class="mt-1 text-sm text-slate-500">Use your tenant admin credentials. Login path is always <span class="font-semibold text-slate-700">/login</span>.</p>

                    <x-auth-session-status class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

                    @if ($errors->any())
                        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="mt-5 space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Email address') }}</label>
                            <input
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="email@example.com"
                                class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200"
                            />
                        </div>

                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3">
                                <label for="password" class="block text-sm font-semibold text-slate-700">{{ __('Password') }}</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-600">
                                        {{ __('Forgot password?') }}
                                    </a>
                                @endif
                            </div>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="current-password"
                                placeholder="{{ __('Password') }}"
                                class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-indigo-600 focus:ring-2 focus:ring-indigo-200"
                            />
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                @checked(old('remember'))
                                class="h-4 w-4 rounded border-slate-300 text-indigo-700 focus:ring-indigo-500"
                            />
                            <span>{{ __('Remember me') }}</span>
                        </label>

                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-indigo-600 text-sm font-semibold text-white transition hover:bg-indigo-500">
                            {{ __('Log in') }}
                        </button>
                    </form>
                </section>
            </main>
        </div>

        @fluxScripts
    </body>
</html>
