<?php

use App\Concerns\ProfileValidationRules;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\Component;

new class extends Component {
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $websiteName = '';
    public ?TemporaryUploadedFile $websiteLogo = null;
    public ?string $currentWebsiteLogo = null;
    public string $showroomAppearance = 'light';
    public string $contactNumber = '';
    public string $contactFacebook = '';
    public string $contactGmail = '';
    public string $contactInstagram = '';
    public string $contactViber = '';
    public string $contactTelegram = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->websiteName = AppSetting::get('site_name', config('app.name', 'Condo Showroom')) ?? config('app.name', 'Condo Showroom');
        $this->currentWebsiteLogo = AppSetting::get('site_logo_path');
        $this->showroomAppearance = AppSetting::get('showroom_appearance', 'light') === 'dark'
            ? 'dark'
            : 'light';
        $this->contactNumber = AppSetting::get('contact_number', '') ?? '';
        $this->contactFacebook = AppSetting::get('contact_facebook', '') ?? '';
        $this->contactGmail = AppSetting::get('contact_gmail', '') ?? '';
        $this->contactInstagram = AppSetting::get('contact_instagram', '') ?? '';
        $this->contactViber = AppSetting::get('contact_viber', '') ?? '';
        $this->contactTelegram = AppSetting::get('contact_telegram', '') ?? '';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('admin.dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function updateWebsiteBranding(): void
    {
        if (! $this->canManageBranding) {
            abort(403);
        }

        $validated = $this->validate([
            'websiteName' => ['required', 'string', 'max:80'],
            'websiteLogo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'showroomAppearance' => ['required', Rule::in(['light', 'dark'])],
        ]);

        $logoPath = $this->currentWebsiteLogo;

        if ($this->websiteLogo instanceof TemporaryUploadedFile) {
            if (is_string($logoPath) && $logoPath !== '') {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $this->websiteLogo->store('branding', 'public');
        }

        AppSetting::put('site_name', trim($validated['websiteName']));
        AppSetting::put('site_logo_path', $logoPath);
        AppSetting::put('showroom_appearance', $validated['showroomAppearance']);

        $this->websiteName = trim($validated['websiteName']);
        $this->currentWebsiteLogo = $logoPath;
        $this->showroomAppearance = $validated['showroomAppearance'];
        $this->websiteLogo = null;

        $this->dispatch('branding-updated');
    }

    public function removeWebsiteLogo(): void
    {
        if (! $this->canManageBranding) {
            abort(403);
        }

        if (is_string($this->currentWebsiteLogo) && $this->currentWebsiteLogo !== '') {
            Storage::disk('public')->delete($this->currentWebsiteLogo);
        }

        AppSetting::put('site_logo_path', null);

        $this->currentWebsiteLogo = null;
        $this->websiteLogo = null;

        $this->dispatch('branding-updated');
    }

    public function updateContactDetails(): void
    {
        if (! $this->canManageBranding) {
            abort(403);
        }

        $validated = $this->validate([
            'contactNumber' => ['required', 'string', 'max:40'],
            'contactFacebook' => ['nullable', 'string', 'max:255'],
            'contactGmail' => ['nullable', 'email', 'max:255'],
            'contactInstagram' => ['nullable', 'string', 'max:255'],
            'contactViber' => ['nullable', 'string', 'max:255'],
            'contactTelegram' => ['nullable', 'string', 'max:255'],
        ]);

        AppSetting::put('contact_number', trim($validated['contactNumber']));
        AppSetting::put('contact_facebook', trim((string) ($validated['contactFacebook'] ?? '')) ?: null);
        AppSetting::put('contact_gmail', trim((string) ($validated['contactGmail'] ?? '')) ?: null);
        AppSetting::put('contact_instagram', trim((string) ($validated['contactInstagram'] ?? '')) ?: null);
        AppSetting::put('contact_viber', trim((string) ($validated['contactViber'] ?? '')) ?: null);
        AppSetting::put('contact_telegram', trim((string) ($validated['contactTelegram'] ?? '')) ?: null);

        $this->contactNumber = trim($validated['contactNumber']);
        $this->contactFacebook = trim((string) ($validated['contactFacebook'] ?? ''));
        $this->contactGmail = trim((string) ($validated['contactGmail'] ?? ''));
        $this->contactInstagram = trim((string) ($validated['contactInstagram'] ?? ''));
        $this->contactViber = trim((string) ($validated['contactViber'] ?? ''));
        $this->contactTelegram = trim((string) ($validated['contactTelegram'] ?? ''));

        $this->dispatch('contact-details-updated');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        $user = Auth::user();

        if (! $user || (bool) $user->is_admin) {
            return false;
        }

        return ! ($user instanceof MustVerifyEmail)
            || ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail());
    }

    #[Computed]
    public function canManageBranding(): bool
    {
        return (bool) Auth::user()?->is_admin;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->canManageBranding)
            <div class="my-8 border-t border-zinc-200 pt-6">
                <flux:heading size="sm">{{ __('Website Branding') }}</flux:heading>
                <flux:subheading>{{ __('Change website name and logo for the public and admin interface.') }}</flux:subheading>

                <form wire:submit="updateWebsiteBranding" class="mt-5 space-y-5">
                    <flux:input
                        wire:model="websiteName"
                        :label="__('Website Name')"
                        type="text"
                        required
                        maxlength="80"
                    />

                    <flux:select wire:model="showroomAppearance" :label="__('Showroom Theme')" required>
                        <option value="light">{{ __('Light') }}</option>
                        <option value="dark">{{ __('Dark') }}</option>
                    </flux:select>
                    <p class="-mt-2 text-xs text-zinc-500">{{ __('Applied to this tenant showroom only.') }}</p>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700">{{ __('Website Logo') }}</label>

                        @if ($websiteLogo)
                            <img src="{{ $websiteLogo->temporaryUrl() }}" alt="{{ __('Website logo preview') }}" class="mb-3 h-14 w-14 rounded-lg object-cover ring-1 ring-zinc-200" />
                        @elseif ($currentWebsiteLogo)
                            <img src="{{ Storage::url($currentWebsiteLogo) }}" alt="{{ __('Current website logo') }}" class="mb-3 h-14 w-14 rounded-lg object-cover ring-1 ring-zinc-200" />
                        @endif

                        <input
                            type="file"
                            wire:model="websiteLogo"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="block w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-200"
                        />

                        @error('websiteLogo')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror

                        <p class="mt-2 text-xs text-zinc-500">{{ __('Accepted: JPG, PNG, WEBP. Max 2MB.') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button variant="primary" type="submit">{{ __('Save Branding') }}</flux:button>

                        @if ($currentWebsiteLogo || $websiteLogo)
                            <flux:button type="button" variant="ghost" wire:click="removeWebsiteLogo">
                                {{ __('Remove Logo') }}
                            </flux:button>
                        @endif

                        <x-action-message on="branding-updated">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </div>

            <div class="my-8 border-t border-zinc-200 pt-6">
                <flux:heading size="sm">{{ __('Contact Details') }}</flux:heading>
                <flux:subheading>{{ __('Shown in the showroom Contact section.') }}</flux:subheading>

                <form wire:submit="updateContactDetails" class="mt-5 space-y-4">
                    <flux:input wire:model="contactNumber" :label="__('Contact Number')" type="text" required />
                    <flux:input wire:model="contactFacebook" :label="__('Facebook')" type="text" />
                    <flux:input wire:model="contactGmail" :label="__('Gmail')" type="email" />
                    <flux:input wire:model="contactInstagram" :label="__('Instagram')" type="text" />
                    <flux:input wire:model="contactViber" :label="__('Viber')" type="text" />
                    <flux:input wire:model="contactTelegram" :label="__('Telegram')" type="text" />

                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button variant="primary" type="submit">{{ __('Save Contact Details') }}</flux:button>
                        <x-action-message on="contact-details-updated">
                            {{ __('Saved.') }}
                        </x-action-message>
                    </div>
                </form>
            </div>
        @endif

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
