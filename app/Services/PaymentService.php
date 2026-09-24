<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly InvoiceService $invoices) {}

    /**
     * Record a payment against an invoice and update its status.
     *
     * The invoice row is locked first, so two payments submitted at the same
     * time cannot both pass the "does not exceed the balance" check.
     *
     * @param  array{amount: string, paid_at: string, method: string, reference?: ?string, notes?: ?string}  $data
     */
    public function record(Invoice $invoice, array $data, ?User $recordedBy = null): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $recordedBy): Payment {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            $amount = Money::of($data['amount']);
            $balance = $invoice->balance();

            if (! $amount->isPositive()) {
                throw ValidationException::withMessages(['amount' => 'The payment amount must be greater than zero.']);
            }

            if ($amount->greaterThan($balance)) {
                throw ValidationException::withMessages([
                    'amount' => "The payment exceeds the outstanding balance of {$balance->formatWithCurrency()}.",
                ]);
            }

            $payment = new Payment([
                'paid_at' => $data['paid_at'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $payment->amount = $amount;
            $payment->invoice()->associate($invoice);
            $payment->recorder()->associate($recordedBy);
            $payment->save();

            $this->invoices->refreshBalance($invoice);

            return $payment;
        });
    }

    public function delete(Payment $payment): void
    {
        DB::transaction(function () use ($payment): void {
            $invoice = Invoice::lockForUpdate()->findOrFail($payment->invoice_id);
            $payment->delete();
            $this->invoices->refreshBalance($invoice);
        });
    }
}
