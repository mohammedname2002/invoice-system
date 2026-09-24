@props(['label', 'value', 'hint' => null, 'tone' => 'slate'])

@php
    $tones = [
        'slate' => 'text-slate-900',
        'rose' => 'text-rose-600',
        'emerald' => 'text-emerald-600',
        'indigo' => 'text-indigo-600',
    ];
@endphp

<div class="card px-5 py-4">
    <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold tabular-nums {{ $tones[$tone] ?? $tones['slate'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
