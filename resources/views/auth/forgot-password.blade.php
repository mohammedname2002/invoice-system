<x-guest-layout title="Forgot password">
    <h1 class="text-lg font-semibold text-slate-900">Reset your password</h1>
    <p class="mt-2 text-sm text-slate-600">
        Enter your email address and we will send you a link to choose a new password.
    </p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm link" href="{{ route('login') }}">Back to login</a>
            <x-primary-button>Email reset link</x-primary-button>
        </div>
    </form>
</x-guest-layout>
