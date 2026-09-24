@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
         class="flex items-start justify-between gap-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-600/20" role="status">
        <span>{{ session('status') }}</span>
        <button type="button" class="text-emerald-700 hover:text-emerald-900" @click="show = false">&times;</button>
    </div>
@endif

@php($domainErrors = collect(['invoice', 'customer', 'items'])->filter(fn ($key) => $errors->has($key)))
@if ($domainErrors->isNotEmpty())
    <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-600/20" role="alert">
        @foreach ($domainErrors as $key)
            <p>{{ $errors->first($key) }}</p>
        @endforeach
    </div>
@endif
