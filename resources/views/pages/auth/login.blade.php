@php
    $slides = collect(glob(base_path('images/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP,AVIF}'), GLOB_BRACE))
        ->map(fn (string $path) => route('project.image', ['filename' => basename($path)]))
        ->values();
    $brandName = \App\Models\AppSetting::get('site_name', config('app.name', 'Condo Showroom')) ?? config('app.name', 'Condo Showroom');
@endphp

<x-layouts::auth.immersive>
    <section class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/60">
                <div class="grid grid-cols-1 lg:min-h-[680px] lg:grid-cols-2">
                    <div class="flex flex-col bg-white px-7 py-8 sm:px-10 sm:py-10">
                        <div class="text-base font-bold tracking-[0.22em] text-emerald-800">{{ $brandName }}</div>

                        <div class="mt-10 max-w-md">
                            <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">{{ __('Admin Portal Login') }}</h1>
                            <p class="mt-2 text-sm text-slate-500">{{ __('Sign in using your admin account credentials.') }}</p>

                            <x-auth-session-status class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

                            @if ($errors->any())
                                <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-4">
                                @csrf

                                <div>
                                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ __('Email address') }}</label>
                                    <input
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        type="email"
                                        required
                                        autofocus
                                        autocomplete="email"
                                        placeholder="email@example.com"
                                        class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200"
                                    />
                                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <div class="mb-1.5 flex items-center justify-between gap-3">
                                        <label for="password" class="block text-sm font-semibold text-slate-700">{{ __('Password') }}</label>
                                        @if (Route::has('password.request'))
                                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-600">
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
                                        class="h-11 w-full rounded-lg border border-slate-300 px-3 text-sm text-slate-900 outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200"
                                    />
                                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        value="1"
                                        @checked(old('remember'))
                                        class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-500"
                                    />
                                    <span>{{ __('Remember me') }}</span>
                                </label>

                                <button
                                    type="submit"
                                    class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-emerald-700 text-sm font-semibold text-white transition hover:bg-emerald-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 focus-visible:ring-offset-2"
                                    data-test="login-button"
                                >
                                    {{ __('Log in') }}
                                </button>
                            </form>
                        </div>

                        <div class="mt-auto pt-10 text-xs text-slate-400">
                            {{ __('Secure admin access only') }}
                        </div>
                    </div>

                    <div class="relative hidden overflow-hidden bg-gradient-to-br from-emerald-700 via-emerald-600 to-emerald-800 lg:block">
                        <div class="relative z-20 flex items-center justify-between px-8 py-7 text-sm font-medium text-emerald-50/90">
                            <div class="flex items-center gap-8">
                                <span>{{ __('Dashboard') }}</span>
                                <span>{{ __('Analytics') }}</span>
                                <span>{{ __('Security') }}</span>
                            </div>
                            <a href="{{ route('home') }}" class="rounded-xl bg-white px-4 py-2 text-emerald-800 shadow-sm">{{ __('Back to site') }}</a>
                        </div>

                        @if ($slides->isNotEmpty())
                            <img
                                src="{{ $slides->first() }}"
                                alt="{{ __('Admin portal preview image') }}"
                                class="h-[calc(100%-72px)] w-full object-cover object-center"
                                loading="lazy"
                            />
                            <div class="absolute inset-x-0 bottom-0 top-[72px] bg-gradient-to-t from-emerald-950/35 via-transparent to-transparent"></div>
                        @else
                            <div class="absolute inset-0 grid place-content-center text-center text-emerald-100">
                                <p class="text-lg font-semibold">{{ __('Admin access secured') }}</p>
                                <p class="mt-2 text-sm text-emerald-100/80">{{ __('No preview image available.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::auth.immersive>
