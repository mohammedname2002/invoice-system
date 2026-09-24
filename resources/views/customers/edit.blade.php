<x-app-layout :title="'Edit '.$customer->name">
    <x-page-header :title="'Edit '.$customer->name" />

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="card p-6 sm:p-8">
        @method('PUT')
        @include('customers._form')
    </form>
</x-app-layout>
