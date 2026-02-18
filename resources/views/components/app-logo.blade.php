@props([
    'sidebar' => false,
])

@php
    $brandName = \App\Models\AppSetting::get('site_name', config('app.name', 'Condo Showroom')) ?? config('app.name', 'Condo Showroom');
    $brandLogoPath = \App\Models\AppSetting::get('site_logo_path');
    $brandLogoUrl = $brandLogoPath ? \Illuminate\Support\Facades\Storage::url($brandLogoPath) : null;
@endphp

@if($sidebar)
    <flux:sidebar.brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            @if ($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="size-8 rounded-md object-cover" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$brandName" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            @if ($brandLogoUrl)
                <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="size-8 rounded-md object-cover" />
            @else
                <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
            @endif
        </x-slot>
    </flux:brand>
@endif
