<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCreditNoteRequest;
use App\Http\Requests\UpdateCreditNoteRequest;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Services\CreditNoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CreditNoteController extends Controller
{
    public function __construct(private readonly CreditNoteService $creditNotes) {}

    public function index(): View
    {
        return view('credit-notes.index');
    }

    /**
     * Step 1: pick an invoice. Step 2 (?invoice=ID): choose what to credit.
     */
    public function create(Request $request): View
    {
        $invoice = $request->filled('invoice')
            ? Invoice::with('customer')->find($request->integer('invoice'))
            : null;

        return view('credit-notes.create', [
            'invoices' => Invoice::with('customer')->latest('issue_date')->latest('id')->limit(200)->get(),
            'invoice' => $invoice,
            'items' => $invoice ? $this->creditNotes->creditableItems($invoice) : collect(),
        ]);
    }

    public function store(StoreCreditNoteRequest $request): RedirectResponse
    {
        $invoice = Invoice::findOrFail($request->integer('invoice_id'));
        $creditNote = $this->creditNotes->create($invoice, $request->validated());

        return to_route('credit-notes.show', $creditNote)->with('status', "Credit note {$creditNote->number} issued.");
    }

    public function show(CreditNote $creditNote): View
    {
        return view('credit-notes.show', ['creditNote' => $creditNote->load(['invoice.customer', 'items'])]);
    }

    public function edit(CreditNote $creditNote): View
    {
        return view('credit-notes.edit', ['creditNote' => $creditNote->load('invoice')]);
    }

    public function update(UpdateCreditNoteRequest $request, CreditNote $creditNote): RedirectResponse
    {
        $this->creditNotes->update($creditNote, $request->validated());

        return to_route('credit-notes.show', $creditNote)->with('status', 'Credit note updated.');
    }

    public function destroy(CreditNote $creditNote): RedirectResponse
    {
        $this->creditNotes->delete($creditNote);

        return to_route('credit-notes.index')->with('status', "Credit note {$creditNote->number} deleted and stock reversed.");
    }

    public function pdf(CreditNote $creditNote): Response
    {
        $creditNote->load(['invoice.customer', 'items']);

        return Pdf::loadView('pdf.credit-note', ['creditNote' => $creditNote])
            ->setPaper('a4')
            ->download("{$creditNote->number}.pdf");
    }
}
