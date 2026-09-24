<x-app-layout :title="'Edit '.$invoice->number">
    <x-page-header :title="'Edit '.$invoice->number" subtitle="Lines can be changed until a payment or credit note is recorded." />

    <form method="POST" action="{{ route('invoices.update', $invoice) }}">
        @method('PUT')
        @include('invoices._form')
    </form>
</x-app-layout>
