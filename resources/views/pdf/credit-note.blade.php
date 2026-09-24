@extends('pdf.layout', ['documentTitle' => $creditNote->number, 'heading' => 'CREDIT NOTE'])

@section('meta')
    <table class="meta" style="width: auto; margin-left: auto; margin-top: 6px;">
        <tr><td class="muted">Credit note no.</td><td class="right" style="padding-left: 12px;"><strong>{{ $creditNote->number }}</strong></td></tr>
        <tr><td class="muted">Date</td><td class="right" style="padding-left: 12px;">{{ $creditNote->issue_date->format('d M Y') }}</td></tr>
        <tr><td class="muted">Original invoice</td><td class="right" style="padding-left: 12px;">{{ $creditNote->invoice->number }}</td></tr>
    </table>
@endsection

@section('content')
    <div class="box">
        <div class="muted">Customer</div>
        <strong>{{ $creditNote->invoice->customer->name }}</strong><br>
        @if ($creditNote->invoice->customer->tax_number)TRN {{ $creditNote->invoice->customer->tax_number }}<br>@endif
        @if ($creditNote->invoice->customer->address){{ $creditNote->invoice->customer->address }}@endif
        @if ($creditNote->reason)
            <div style="margin-top: 6px;"><span class="muted">Reason:</span> {{ $creditNote->reason }}</div>
        @endif
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right" style="width: 9%">Qty</th>
                <th class="right" style="width: 12%">Unit price</th>
                <th class="right" style="width: 12%">Discount</th>
                <th class="right" style="width: 12%">VAT</th>
                <th class="right" style="width: 13%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($creditNote->items as $item)
                <tr>
                    <td>{{ $item->description }}@if ($item->line_note)<br><span class="muted">{{ $item->line_note }}</span>@endif</td>
                    <td class="right">{{ $item->quantity }}@if ($item->free_quantity) +{{ $item->free_quantity }}@endif</td>
                    <td class="right">{{ $item->unit_price->format() }}</td>
                    <td class="right">{{ $item->discount_amount->isZero() ? '-' : $item->discount_amount->format() }}</td>
                    <td class="right">{{ $item->tax_amount->format() }}</td>
                    <td class="right">{{ $item->total->format() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="right">{{ $creditNote->subtotal->format() }}</td></tr>
        @unless ($creditNote->discount_total->isZero())
            <tr><td class="muted">Discount</td><td class="right">{{ $creditNote->discount_total->formatAsDeduction() }}</td></tr>
        @endunless
        <tr><td class="muted">VAT</td><td class="right">{{ $creditNote->tax_total->format() }}</td></tr>
        <tr class="grand"><td>Total credited {{ config('invoicing.currency') }}</td><td class="right">{{ $creditNote->total->format() }}</td></tr>
    </table>
@endsection
