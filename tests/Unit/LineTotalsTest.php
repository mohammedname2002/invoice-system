<?php

namespace Tests\Unit;

use App\Support\LineTotals;
use App\Support\Money;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LineTotalsTest extends TestCase
{
    #[Test]
    public function it_applies_the_discount_before_vat(): void
    {
        // 4 x 12.50 = 50.00; 10% discount = 5.00; net 45.00; 5% VAT = 2.25; total 47.25
        $line = LineTotals::calculate(Money::of('12.50'), 4, '10.00', '5.00');

        $this->assertSame(5000, $line->subtotal->minor);
        $this->assertSame(500, $line->discount->minor);
        $this->assertSame(4500, $line->net->minor);
        $this->assertSame(225, $line->tax->minor);
        $this->assertSame(4725, $line->total->minor);
    }

    #[Test]
    public function each_step_is_rounded_so_columns_add_up(): void
    {
        // 3 x 3.33 = 9.99; 7.5% discount = 0.749 -> 0.75; net 9.24; 5% VAT = 0.462 -> 0.46
        $line = LineTotals::calculate(Money::of('3.33'), 3, '7.50', '5.00');

        $this->assertSame(999, $line->subtotal->minor);
        $this->assertSame(75, $line->discount->minor);
        $this->assertSame(924, $line->net->minor);
        $this->assertSame(46, $line->tax->minor);
        $this->assertSame(970, $line->total->minor);
        $this->assertSame($line->subtotal->minor, $line->net->minor + $line->discount->minor);
        $this->assertSame($line->total->minor, $line->net->minor + $line->tax->minor);
    }

    #[Test]
    public function zero_rates_leave_the_amount_untouched(): void
    {
        $line = LineTotals::calculate(Money::of('19.99'), 2, 0, 0);

        $this->assertSame(3998, $line->total->minor);
        $this->assertTrue($line->discount->isZero());
        $this->assertTrue($line->tax->isZero());
    }

    #[Test]
    public function document_totals_are_the_sum_of_rounded_lines(): void
    {
        $totals = LineTotals::sum([
            LineTotals::calculate(Money::of('12.50'), 4, '10.00', '5.00'),
            LineTotals::calculate(Money::of('3.33'), 3, '7.50', '5.00'),
            LineTotals::calculate(Money::of('15.00'), 1, 0, '5.00'),
        ]);

        $this->assertSame(5000 + 999 + 1500, $totals->subtotal->minor);
        $this->assertSame(500 + 75, $totals->discount->minor);
        $this->assertSame(225 + 46 + 75, $totals->tax->minor);
        $this->assertSame(4725 + 970 + 1575, $totals->total->minor);
    }
}
