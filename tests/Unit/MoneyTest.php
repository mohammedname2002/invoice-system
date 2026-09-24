<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /** @return array<string, array{0: string|int|float, 1: int}> */
    public static function decimalInputs(): array
    {
        return [
            'whole number string' => ['12', 1200],
            'two decimals' => ['12.34', 1234],
            'one decimal' => ['12.5', 1250],
            'leading dot' => ['.5', 50],
            'rounds half up' => ['0.125', 13],
            'rounds down' => ['0.124', 12],
            'negative' => ['-3.10', -310],
            'integer' => [7, 700],
            'float without artefacts' => [0.1 + 0.2, 30],
        ];
    }

    #[Test]
    #[DataProvider('decimalInputs')]
    public function it_parses_decimal_amounts_into_minor_units(string|int|float $input, int $expected): void
    {
        $this->assertSame($expected, Money::of($input)->minor);
    }

    #[Test]
    public function it_rejects_non_numeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::of('12,50');
    }

    #[Test]
    public function arithmetic_is_exact(): void
    {
        $a = Money::of('0.10');
        $b = Money::of('0.20');

        $this->assertSame(30, $a->add($b)->minor);
        $this->assertSame(-10, $a->subtract($b)->minor);
        $this->assertSame(250, Money::of('0.25')->multiply(10)->minor);
        $this->assertSame(60, Money::sum([$a, $b, $b, $a])->minor);
    }

    #[Test]
    public function percentages_round_half_up_to_the_cent(): void
    {
        // 5% of 10.10 = 0.505 -> 0.51
        $this->assertSame(51, Money::of('10.10')->percentage('5')->minor);
        // 7.5% of 19.99 = 1.49925 -> 1.50
        $this->assertSame(150, Money::of('19.99')->percentage('7.50')->minor);
        // 5% of 10.09 = 0.5045 -> 0.50
        $this->assertSame(50, Money::of('10.09')->percentage(5)->minor);
        // Negative amounts round away from zero, symmetric with positives.
        $this->assertSame(-51, Money::of('-10.10')->percentage('5')->minor);
        $this->assertSame(0, Money::of('99.99')->percentage('0.00')->minor);
    }

    #[Test]
    public function it_formats_amounts(): void
    {
        $this->assertSame('1,234,567.05', Money::ofMinor(123456705)->format());
        $this->assertSame('-0.05', Money::ofMinor(-5)->format());
        $this->assertSame('1250.50', Money::of('1250.5')->toDecimal());
        $this->assertSame('0.00', Money::zero()->toDecimal());
        $this->assertSame('-12.50', Money::of('12.5')->formatAsDeduction());
        $this->assertSame('0.00', Money::zero()->formatAsDeduction());
    }

    #[Test]
    public function comparisons(): void
    {
        $this->assertTrue(Money::of('1')->greaterThan(Money::of('0.99')));
        $this->assertTrue(Money::of('1')->equals(Money::ofMinor(100)));
        $this->assertTrue(Money::zero()->isZero());
        $this->assertTrue(Money::of('-1')->isNegative());
        $this->assertFalse(Money::zero()->isPositive());
    }
}
