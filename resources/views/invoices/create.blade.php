<x-app-layout title="New invoice">
    <x-page-header title="New invoice" subtitle="The invoice number is assigned when you save." />

    <form method="POST" action="{{ route('invoices.store') }}">
        @include('invoices._form')
    </form>
</x-app-layout>
