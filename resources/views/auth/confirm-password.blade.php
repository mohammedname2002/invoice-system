<x-guest-layout title="Confirm password">
    <h1 class="text-lg font-semibold text-slate-900">Confirm your password</h1>
    <p class="mt-2 text-sm text-slate-600">This is a secure area of the application. Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Confirm</x-primary-button>
    </form>
</x-guest-layout>
