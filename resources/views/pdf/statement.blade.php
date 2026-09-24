@extends('pdf.layout', ['documentTitle' => 'Statement '.$customer->name, 'heading' => 'STATEMENT'])

@section('meta')
    <table class="meta" style="width: auto; margin-left: auto; margin-top: 6px;">
        <tr><td class="muted">Period</td><td class="right" style="padding-left: 12px;">{{ $month || $year ? trim(($month ? \Illuminate\Support\Carbon::create(null, $month, 1)->format('F') : '').' '.($year ?? '')) : 'All time' }}</td></tr>
        <tr><td class="muted">Generated</td><td class="right" style="padding-left: 12px;">{{ now()->format('d M Y') }}</td></tr>
    </table>
@endsection

@section('content')
    <div class="box">
        <div class="muted">Customer</div>
        <strong>{{ $customer->name }}</strong><br>
        @if ($customer->tax_number)TRN {{ $customer->tax_number }}<br>@endif
        @if ($customer->address){{ $customer->address }}@endif
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Issued</th>
                <th>Due</th>
                <th class="right">Total</th>
                <th class="right">Credited</th>
                <th class="right">Paid</th>
                <th class="right">Balance</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->issue_date->format('d M Y') }}</td>
                    <td>{{ $invoice->due_date->format('d M Y') }}</td>
                    <td class="right">{{ $invoice->total->format() }}</td>
                    <td class="right">{{ $invoice->amount_credited->format() }}</td>
                    <td class="right">{{ $invoice->amount_paid->format() }}</td>
                    <td class="right">{{ $invoice->balance()->format() }}</td>
                    <td>{{ $invoice->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No invoices in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Invoiced</td><td class="right">{{ $totals['invoiced']->format() }}</td></tr>
        <tr><td class="muted">Credited</td><td class="right">{{ $totals['credited']->formatAsDeduction() }}</td></tr>
        <tr><td class="muted">Paid</td><td class="right">{{ $totals['paid']->formatAsDeduction() }}</td></tr>
        <tr class="grand"><td>Balance {{ config('invoicing.currency') }}</td><td class="right">{{ $totals['balance']->format() }}</td></tr>
    </table>
@endsection
