<x-guest-layout title="Log in">
    <h1 class="text-lg font-semibold text-slate-900">Sign in to your account</h1>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Password" />
                @if (Route::has('password.request'))
                    <a class="text-sm link" href="{{ route('password.request') }}">Forgot your password?</a>
                @endif
            </div>
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" name="remember">
            Remember me
        </label>

        <x-primary-button class="w-full">Log in</x-primary-button>
    </form>

    @if (app()->environment('local'))
        <p class="mt-6 rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-500">
            Demo login: <span class="font-mono">admin@example.com</span> / <span class="font-mono">password</span>
        </p>
    @endif
</x-guest-layout>
