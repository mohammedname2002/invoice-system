<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\CreditNote;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Product;

/**
 * Keeps product stock in step with invoices (stock out) and credit notes
 * (stock back in). Every change is written as an InventoryMovement so the
 * stock level can always be explained.
 *
 * Callers are expected to wrap these methods in their own transaction.
 */
class InventoryService
{
    public function recordSale(Invoice $invoice): void
    {
        foreach ($invoice->items()->whereNotNull('product_id')->get() as $item) {
            $this->move(
                productId: $item->product_id,
                type: InventoryMovementType::Sale,
                quantity: -($item->quantity + $item->free_quantity),
                references: ['invoice_id' => $invoice->id],
            );
        }
    }

    public function reverseSale(Invoice $invoice): void
    {
        $this->reverse(
            InventoryMovement::query()
                ->where('invoice_id', $invoice->id)
                ->where('type', InventoryMovementType::Sale)
                ->get()
        );
    }

    public function recordReturn(CreditNote $creditNote): void
    {
        foreach ($creditNote->items()->whereNotNull('product_id')->get() as $item) {
            $units = $item->quantity + $item->free_quantity;

            if ($units === 0) {
                continue;
            }

            $this->move(
                productId: $item->product_id,
                type: InventoryMovementType::CreditReturn,
                quantity: $units,
                references: ['credit_note_id' => $creditNote->id],
                note: $item->line_note,
            );
        }
    }

    public function reverseReturn(CreditNote $creditNote): void
    {
        $this->reverse(
            InventoryMovement::query()
                ->where('credit_note_id', $creditNote->id)
                ->where('type', InventoryMovementType::CreditReturn)
                ->get()
        );
    }

    /**
     * @param  array<string, int>  $references
     */
    private function move(int $productId, InventoryMovementType $type, int $quantity, array $references, ?string $note = null): void
    {
        if ($quantity === 0) {
            return;
        }

        InventoryMovement::create([
            'product_id' => $productId,
            'type' => $type,
            'quantity' => $quantity,
            'note' => $note,
            ...$references,
        ]);

        Product::whereKey($productId)->increment('stock_quantity', $quantity);
    }

    /**
     * @param  iterable<InventoryMovement>  $movements
     */
    private function reverse(iterable $movements): void
    {
        foreach ($movements as $movement) {
            Product::whereKey($movement->product_id)->decrement('stock_quantity', $movement->quantity);
            $movement->delete();
        }
    }
}
