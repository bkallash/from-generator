<div class="space-y-6">
    <div class="mb-6">
        <a href="{{ route('dashboard', ['view' => 'dashboard']) }}" wire:navigate
            class="inline-flex items-center text-sm font-medium text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Dashboard
        </a>
    </div>

    <!-- Success Message -->
    <div x-data="{ show: false, message: '' }"
        x-on:profile-updated.window="show = true; message = 'Profile updated successfully.'; setTimeout(() => show = false, 3000)"
        x-on:password-updated.window="show = true; message = 'Password updated successfully.'; setTimeout(() => show = false, 3000)"
        x-show="show" x-transition class="fixed top-20 right-4 z-50">
        <div class="bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-4 py-3 border-2 border-neutral-900 dark:border-neutral-100 shadow-lg"
            role="alert">
            <span x-text="message" class="block sm:inline font-medium"></span>
        </div>
    </div>

    <!-- Profile Information -->
    <div
        class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 transition-colors duration-300">
        <h2 class="text-2xl font-light tracking-tight mb-3">
            Profile <strong class="font-semibold">Information</strong>
        </h2>
        <p class="text-neutral-600 dark:text-neutral-400 font-light mb-8">
            Update your account's profile information and email address.
        </p>

        <form wire:submit="updateProfile" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium mb-2">Name</label>
                <input type="text" wire:model="name" id="name" required
                    class="w-full px-4 py-3 border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition-all duration-200">
                @error('name')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium mb-2">Email</label>
                <input type="email" wire:model="email" id="email" disabled readonly
                    class="w-full px-4 py-3 border border-neutral-300 dark:border-neutral-700 bg-neutral-100 dark:bg-neutral-800 text-neutral-500 dark:text-neutral-400 cursor-not-allowed transition-all duration-200">
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                    class="bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-6 py-3 text-sm font-medium border-2 border-neutral-900 dark:border-neutral-100 transition-all duration-300 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100">
                    Save Changes
                </button>
                <a href="{{ route('dashboard', ['view' => 'dashboard']) }}" wire:navigate
                    class="px-6 py-3 text-sm font-medium border border-neutral-300 dark:border-neutral-700 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900 hover:text-neutral-900 dark:hover:text-neutral-100 transition-all duration-300 inline-flex items-center">
                    Cancel
                </a>
                <div wire:loading wire:target="updateProfile"
                    class="text-sm text-neutral-500 dark:text-neutral-400 font-light">
                    Saving...
                </div>
            </div>
        </form>
    </div>

    @if (!auth()->user()->google_id)
        <!-- Update Password -->
        <div
            class="bg-white dark:bg-neutral-950 border border-neutral-200 dark:border-neutral-800 p-8 transition-colors duration-300">
            <h2 class="text-2xl font-light tracking-tight mb-3">
                Update <strong class="font-semibold">Password</strong>
            </h2>
            <p class="text-neutral-600 dark:text-neutral-400 font-light mb-8">
                Ensure your account is using a long, random password to stay secure.
            </p>

            <form wire:submit="updatePassword" class="space-y-6">
                <div>
                    <label for="current_password" class="block text-sm font-medium mb-2">Current Password</label>
                    <input type="password" wire:model="current_password" id="current_password"
                        class="w-full px-4 py-3 border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition-all duration-200">
                    @error('current_password')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-2">New Password</label>
                    <input type="password" wire:model="password" id="password"
                        class="w-full px-4 py-3 border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition-all duration-200">
                    @error('password')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium mb-2">Confirm Password</label>
                    <input type="password" wire:model="password_confirmation" id="password_confirmation"
                        class="w-full px-4 py-3 border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-neutral-100 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition-all duration-200">
                    @error('password_confirmation')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-4">
                    <button type="submit"
                        class="bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 px-6 py-3 text-sm font-medium border-2 border-neutral-900 dark:border-neutral-100 transition-all duration-300 hover:bg-transparent dark:hover:bg-transparent hover:text-neutral-900 dark:hover:text-neutral-100">
                        Update Password
                    </button>
                    <a href="{{ route('dashboard', ['view' => 'dashboard']) }}" wire:navigate
                        class="px-6 py-3 text-sm font-medium border border-neutral-300 dark:border-neutral-700 text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900 hover:text-neutral-900 dark:hover:text-neutral-100 transition-all duration-300 inline-flex items-center">
                        Cancel
                    </a>
                    <div wire:loading wire:target="updatePassword"
                        class="text-sm text-neutral-500 dark:text-neutral-400 font-light">
                        Saving...
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>
