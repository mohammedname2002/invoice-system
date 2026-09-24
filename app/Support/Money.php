<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable money amount stored as an integer number of minor units (cents).
 *
 * All arithmetic is integer based, so totals never pick up floating point
 * noise. Percentages are applied with half-up rounding to the nearest cent,
 * which is how the amounts are shown on the printed invoice.
 */
final class Money implements JsonSerializable, Stringable
{
    private const SCALE = 2;

    private function __construct(public readonly int $minor) {}

    public static function ofMinor(int $minor): self
    {
        return new self($minor);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    /**
     * Build from a decimal amount such as "1250.5" or 99.99.
     *
     * Strings are parsed digit by digit; floats are converted through their
     * string form so that 0.1 + 0.2 style artefacts never reach the database.
     */
    public static function of(string|int|float $amount): self
    {
        return new self(self::decimalToScaledInt($amount, self::SCALE));
    }

    /** @param  iterable<self>  $amounts */
    public static function sum(iterable $amounts): self
    {
        $total = 0;

        foreach ($amounts as $amount) {
            $total += $amount->minor;
        }

        return new self($total);
    }

    public function add(self $other): self
    {
        return new self($this->minor + $other->minor);
    }

    public function subtract(self $other): self
    {
        return new self($this->minor - $other->minor);
    }

    public function multiply(int $factor): self
    {
        return new self($this->minor * $factor);
    }

    /**
     * Return the given percentage of this amount, e.g. percentage("5.00") for 5% VAT.
     * Rates are accepted with up to two decimals.
     */
    public function percentage(string|int|float $rate): self
    {
        $basisPoints = self::decimalToScaledInt($rate, 2);

        return new self(self::divideRoundHalfUp($this->minor * $basisPoints, 10_000));
    }

    public function negate(): self
    {
        return new self(-$this->minor);
    }

    public function isZero(): bool
    {
        return $this->minor === 0;
    }

    public function isPositive(): bool
    {
        return $this->minor > 0;
    }

    public function isNegative(): bool
    {
        return $this->minor < 0;
    }

    public function equals(self $other): bool
    {
        return $this->minor === $other->minor;
    }

    public function greaterThan(self $other): bool
    {
        return $this->minor > $other->minor;
    }

    /** Plain decimal string, e.g. "1250.50". Suitable for form inputs. */
    public function toDecimal(): string
    {
        $sign = $this->minor < 0 ? '-' : '';
        $abs = abs($this->minor);

        return $sign.intdiv($abs, 100).'.'.str_pad((string) ($abs % 100), self::SCALE, '0', STR_PAD_LEFT);
    }

    /** Human readable amount with thousands separators, e.g. "1,250.50". */
    public function format(): string
    {
        $sign = $this->minor < 0 ? '-' : '';
        $abs = abs($this->minor);

        return $sign.number_format(intdiv($abs, 100)).'.'.str_pad((string) ($abs % 100), self::SCALE, '0', STR_PAD_LEFT);
    }

    /** Amount shown as a deduction, e.g. "-12.50" (and "0.00" rather than "-0.00"). */
    public function formatAsDeduction(): string
    {
        return ($this->minor > 0 ? '-' : '').$this->format();
    }

    /** Amount prefixed with the configured currency code, e.g. "USD 1,250.50". */
    public function formatWithCurrency(): string
    {
        return config('invoicing.currency').' '.$this->format();
    }

    public function jsonSerialize(): string
    {
        return $this->toDecimal();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private static function divideRoundHalfUp(int $numerator, int $denominator): int
    {
        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;

        if (abs($remainder) * 2 >= $denominator) {
            $quotient += $numerator < 0 ? -1 : 1;
        }

        return $quotient;
    }

    private static function decimalToScaledInt(string|int|float $value, int $scale): int
    {
        if (is_int($value)) {
            return $value * (10 ** $scale);
        }

        $string = is_float($value) ? sprintf('%.'.($scale + 2).'F', $value) : trim($value);

        if (! preg_match('/^(-)?(\d*)(?:\.(\d*))?$/', $string, $m) || ($m[2] === '' && ($m[3] ?? '') === '')) {
            throw new InvalidArgumentException("Invalid decimal amount [{$value}].");
        }

        $negative = $m[1] === '-';
        $whole = $m[2] === '' ? '0' : $m[2];
        $fraction = $m[3] ?? '';

        // Round half-up on the first digit beyond the requested scale.
        $kept = str_pad(substr($fraction, 0, $scale), $scale, '0');
        $roundUp = strlen($fraction) > $scale && (int) $fraction[$scale] >= 5;

        $scaled = (int) $whole * (10 ** $scale) + (int) $kept + ($roundUp ? 1 : 0);

        return $negative ? -$scaled : $scaled;
    }
}
