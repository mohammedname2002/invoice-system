<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function store(PaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $payment = $this->payments->record($invoice, $request->validated(), $request->user());

        return to_route('invoices.show', $invoice)
            ->with('status', "Payment of {$payment->amount->formatWithCurrency()} recorded.");
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->payments->delete($payment);

        return to_route('invoices.show', $payment->invoice_id)->with('status', 'Payment removed.');
    }
}
