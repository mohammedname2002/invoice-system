<x-app-layout :title="'Edit '.$creditNote->number">
    <x-page-header :title="'Edit '.$creditNote->number" subtitle="Quantities cannot be changed; delete the credit note and issue a new one instead." />

    <form method="POST" action="{{ route('credit-notes.update', $creditNote) }}" class="card space-y-6 p-6 sm:p-8">
        @csrf
        @method('PUT')

        <div class="grid gap-6 sm:grid-cols-3">
            <div>
                <x-input-label for="issue_date" value="Credit note date" />
                <x-text-input id="issue_date" name="issue_date" type="date" class="mt-1" :value="old('issue_date', $creditNote->issue_date->toDateString())" required />
                <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="reason" value="Reason" />
                <x-text-input id="reason" name="reason" class="mt-1" :value="old('reason', $creditNote->reason)" />
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('credit-notes.show', $creditNote) }}" class="btn-secondary">Cancel</a>
            <x-primary-button>Save changes</x-primary-button>
        </div>
    </form>
</x-app-layout>
