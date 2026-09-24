<x-app-layout title="New customer">
    <x-page-header title="New customer" />

    <form method="POST" action="{{ route('customers.store') }}" class="card p-6 sm:p-8">
        @include('customers._form')
    </form>
</x-app-layout>
