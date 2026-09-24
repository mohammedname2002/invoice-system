<?php

namespace App\Support;

/**
 * The money breakdown of one invoice or credit note line.
 *
 *   subtotal = unit price x charged quantity
 *   discount = subtotal x customer discount rate
 *   net      = subtotal - discount
 *   tax      = net x VAT rate
 *   total    = net + tax
 *
 * Free (bonus) units are shipped but never charged, so they do not appear here.
 * The discount is applied before VAT, and each step is rounded to the cent,
 * so the printed columns always add up.
 */
final class LineTotals
{
    public function __construct(
        public readonly Money $subtotal,
        public readonly Money $discount,
        public readonly Money $net,
        public readonly Money $tax,
        public readonly Money $total,
    ) {}

    public static function calculate(
        Money $unitPrice,
        int $quantity,
        string|int|float $discountRate,
        string|int|float $vatRate,
    ): self {
        $subtotal = $unitPrice->multiply($quantity);
        $discount = $subtotal->percentage($discountRate);
        $net = $subtotal->subtract($discount);
        $tax = $net->percentage($vatRate);

        return new self($subtotal, $discount, $net, $tax, $net->add($tax));
    }

    /**
     * Sum several lines into document totals.
     *
     * @param  iterable<self>  $lines
     */
    public static function sum(iterable $lines): self
    {
        $subtotal = $discount = $net = $tax = $total = Money::zero();

        foreach ($lines as $line) {
            $subtotal = $subtotal->add($line->subtotal);
            $discount = $discount->add($line->discount);
            $net = $net->add($line->net);
            $tax = $tax->add($line->tax);
            $total = $total->add($line->total);
        }

        return new self($subtotal, $discount, $net, $tax, $total);
    }
}
