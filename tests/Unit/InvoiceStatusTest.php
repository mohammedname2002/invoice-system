<?php

namespace Tests\Unit;

use App\Enums\InvoiceStatus;
use App\Support\Money;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();
        $this->today = CarbonImmutable::parse('2025-06-15');
    }

    private function resolve(string $total, string $settled, ?string $due): InvoiceStatus
    {
        return InvoiceStatus::resolve(
            Money::of($total),
            Money::of($settled),
            $due ? CarbonImmutable::parse($due) : null,
            $this->today,
        );
    }

    #[Test]
    public function nothing_settled_and_not_yet_due_is_unpaid(): void
    {
        $this->assertSame(InvoiceStatus::Unpaid, $this->resolve('100', '0', '2025-06-30'));
    }

    #[Test]
    public function part_settled_is_partially_paid(): void
    {
        $this->assertSame(InvoiceStatus::PartiallyPaid, $this->resolve('100', '40', '2025-06-30'));
    }

    #[Test]
    public function fully_settled_is_paid_even_when_past_due(): void
    {
        $this->assertSame(InvoiceStatus::Paid, $this->resolve('100', '100', '2025-01-01'));
        $this->assertSame(InvoiceStatus::Paid, $this->resolve('100', '120', '2025-06-30'));
    }

    #[Test]
    public function open_balance_after_the_due_date_is_overdue(): void
    {
        $this->assertSame(InvoiceStatus::Overdue, $this->resolve('100', '0', '2025-06-14'));
        $this->assertSame(InvoiceStatus::Overdue, $this->resolve('100', '99.99', '2025-06-14'));
    }

    #[Test]
    public function the_due_date_itself_is_not_overdue(): void
    {
        $this->assertSame(InvoiceStatus::Unpaid, $this->resolve('100', '0', '2025-06-15'));
    }

    #[Test]
    public function a_zero_total_invoice_is_paid(): void
    {
        $this->assertSame(InvoiceStatus::Paid, $this->resolve('0', '0', '2025-06-30'));
    }
}
