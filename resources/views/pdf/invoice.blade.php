@extends('pdf.layout', ['documentTitle' => $invoice->number, 'heading' => 'TAX INVOICE'])

@section('meta')
    <table class="meta" style="width: auto; margin-left: auto; margin-top: 6px;">
        <tr><td class="muted">Invoice no.</td><td class="right" style="padding-left: 12px;"><strong>{{ $invoice->number }}</strong></td></tr>
        <tr><td class="muted">Issue date</td><td class="right" style="padding-left: 12px;">{{ $invoice->issue_date->format('d M Y') }}</td></tr>
        <tr><td class="muted">Due date</td><td class="right" style="padding-left: 12px;">{{ $invoice->due_date->format('d M Y') }}</td></tr>
    </table>
@endsection

@section('content')
    <div class="box">
        <div class="muted">Bill to</div>
        <strong>{{ $invoice->customer->name }}</strong><br>
        @if ($invoice->customer->tax_number)TRN {{ $invoice->customer->tax_number }}<br>@endif
        @if ($invoice->customer->address){{ $invoice->customer->address }}<br>@endif
        @if ($invoice->customer->phone){{ $invoice->customer->phone }}@endif
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th style="width: 4%">#</th>
                <th>Item</th>
                <th class="right" style="width: 9%">Qty</th>
                <th class="right" style="width: 12%">Unit price</th>
                <th class="right" style="width: 12%">Discount</th>
                <th class="right" style="width: 14%">VAT</th>
                <th class="right" style="width: 13%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->quantity }}@if ($item->free_quantity) +{{ $item->free_quantity }}@endif</td>
                    <td class="right">{{ $item->unit_price->format() }}</td>
                    <td class="right">{{ $item->discount_amount->isZero() ? '-' : $item->discount_amount->format() }}</td>
                    <td class="right">{{ $item->tax_amount->format() }} ({{ $item->vat_rate }}%)</td>
                    <td class="right">{{ $item->total->format() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="right">{{ $invoice->subtotal->format() }}</td></tr>
        @unless ($invoice->discount_total->isZero())
            <tr><td class="muted">Discount ({{ $invoice->discount_rate }}%)</td><td class="right">{{ $invoice->discount_total->formatAsDeduction() }}</td></tr>
        @endunless
        <tr><td class="muted">VAT</td><td class="right">{{ $invoice->tax_total->format() }}</td></tr>
        <tr class="grand"><td>Total {{ config('invoicing.currency') }}</td><td class="right">{{ $invoice->total->format() }}</td></tr>
        @unless ($invoice->amount_credited->isZero() && $invoice->amount_paid->isZero())
            <tr><td class="muted">Credited</td><td class="right">{{ $invoice->amount_credited->formatAsDeduction() }}</td></tr>
            <tr><td class="muted">Paid</td><td class="right">{{ $invoice->amount_paid->formatAsDeduction() }}</td></tr>
            <tr class="grand"><td>Balance due</td><td class="right">{{ $invoice->balance()->format() }}</td></tr>
        @endunless
    </table>

    @if ($invoice->notes)
        <div class="notes"><strong>Notes</strong><br>{{ $invoice->notes }}</div>
    @endif
@endsection
