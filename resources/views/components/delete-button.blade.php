@props(['action', 'confirm' => 'Delete this record? This cannot be undone.', 'label' => 'Delete'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="inline">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'btn-secondary text-rose-600 hover:text-rose-700']) }}>{{ $label }}</button>
</form>
