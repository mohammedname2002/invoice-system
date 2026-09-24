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
<body class="h-full font-sans text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
    {{-- Mobile sidebar backdrop --}}
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false" style="display: none"></div>

    {{-- Sidebar --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 transition-transform lg:translate-x-0"
        :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': ! sidebarOpen }"
    >
        <div class="flex h-16 shrink-0 items-center gap-3 px-6">
            <x-application-logo class="h-8 w-8 text-indigo-400" />
            <span class="text-base font-semibold text-white">{{ config('app.name') }}</span>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <x-nav-item :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">Dashboard</x-nav-item>
            <x-nav-item :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" icon="document">Invoices</x-nav-item>
            <x-nav-item :href="route('credit-notes.index')" :active="request()->routeIs('credit-notes.*')" icon="receipt-refund">Credit notes</x-nav-item>
            <x-nav-item :href="route('customers.index')" :active="request()->routeIs('customers.*')" icon="users">Customers</x-nav-item>
            <x-nav-item :href="route('products.index')" :active="request()->routeIs('products.*')" icon="cube">Products</x-nav-item>
            @can('view-reports')
                <x-nav-item :href="route('reports.index')" :active="request()->routeIs('reports.*')" icon="chart">Reports</x-nav-item>
            @endcan
        </nav>

        <div class="border-t border-slate-800 px-6 py-4 text-xs text-slate-400">
            Signed in as <span class="font-medium text-slate-200">{{ auth()->user()->role->label() }}</span>
        </div>
    </aside>

    <div class="flex min-h-full flex-col lg:pl-64">
        {{-- Top bar --}}
        <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button type="button" class="-m-2 p-2 text-slate-600 lg:hidden" @click="sidebarOpen = true">
                <span class="sr-only">Open sidebar</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
            </button>

            <div class="flex-1"></div>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 rounded-md px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-100">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                            {{ str(auth()->user()->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
                        </span>
                        <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Log out</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </header>

        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <x-flash />
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScriptConfig
</body>
</html>
