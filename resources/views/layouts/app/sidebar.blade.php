<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group heading="Admin" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        Dashboard
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.units.index')" :current="request()->routeIs('admin.units.*')" wire:navigate>
                        Units
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')" wire:navigate>
                        Categories
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.viewing-requests.index')" :current="request()->routeIs('admin.viewing-requests.*')" wire:navigate>
                        Viewing Requests
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.rentals.index')" :current="request()->routeIs('admin.rentals.*')" wire:navigate>
                        Rentals
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('admin.logs.index')" :current="request()->routeIs('admin.logs.*')" wire:navigate>
                        Audit Logs
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <livewire:admin.notifications-bell />

            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                <flux:menu>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        Settings
                    </flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            Log Out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <div class="hidden justify-end px-6 pt-4 lg:flex">
            <livewire:admin.notifications-bell />
        </div>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
