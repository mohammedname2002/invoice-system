<x-app-layout title="New product">
    <x-page-header title="New product" />

    <form method="POST" action="{{ route('products.store') }}" class="card p-6 sm:p-8">
        @include('products._form')
    </form>
</x-app-layout>
