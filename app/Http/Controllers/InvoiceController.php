<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\InvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function index(): View
    {
        return view('invoices.index');
    }

    public function create(Request $request): View
    {
        $invoice = new Invoice([
            'issue_date' => Carbon::today(),
            'customer_id' => $request->integer('customer') ?: null,
        ]);

        return view('invoices.create', $this->formData($invoice));
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoices->create($request->validated());

        return to_route('invoices.show', $invoice)->with('status', "Invoice {$invoice->number} created.");
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'items', 'payments.recorder', 'creditNotes']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'paymentMethods' => PaymentMethod::cases(),
        ]);
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if ($invoice->isLocked()) {
            return to_route('invoices.show', $invoice)
                ->withErrors(['invoice' => 'This invoice has payments or credit notes and can no longer be edited. Issue a credit note instead.']);
        }

        return view('invoices.edit', $this->formData($invoice->load('items')));
    }

    public function update(InvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->invoices->update($invoice, $request->validated());

        return to_route('invoices.show', $invoice)->with('status', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->invoices->delete($invoice);

        return to_route('invoices.index')->with('status', "Invoice {$invoice->number} deleted.");
    }

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['customer', 'items']);

        return Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4')
            ->download("{$invoice->number}.pdf");
    }

    /**
     * Data for the line editor: customers, a JSON-friendly product catalogue
     * and the rows to start with (old input after a validation error wins).
     *
     * @return array<string, mixed>
     */
    private function formData(Invoice $invoice): array
    {
        $rows = old('items', $invoice->exists
            ? $invoice->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'free_quantity' => $item->free_quantity,
                'unit_price' => $item->unit_price->toDecimal(),
                'vat_rate' => $item->vat_rate,
            ])->all()
            : []);

        return [
            'invoice' => $invoice,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'discount_rate']),
            'products' => Product::orderBy('name')->get()->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'unit_price' => $product->unit_price->toDecimal(),
                'vat_rate' => $product->vat_rate,
                'apply_customer_discount' => $product->apply_customer_discount,
            ]),
            'rows' => array_values($rows),
            'defaultVatRate' => config('invoicing.default_vat_rate'),
        ];
    }
}
