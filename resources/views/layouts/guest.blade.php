<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans text-slate-900 antialiased">
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <x-application-logo class="h-10 w-10 text-indigo-600" />
            <span class="text-xl font-semibold text-slate-900">{{ config('app.name') }}</span>
        </a>

        <div class="card mt-8 w-full max-w-md px-6 py-8 sm:px-8">
            {{ $slot }}
        </div>
    </div>

    @livewireScriptConfig
</body>
</html>
