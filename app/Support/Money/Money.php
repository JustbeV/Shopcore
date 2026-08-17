<?php

namespace App\Support\Money;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(
        private int $amountMinor,
        private string $currency,
    ) {}

    public static function fromMinorUnits(int $amountMinor, string $currency): self
    {
        return new self($amountMinor, strtoupper($currency));
    }

    public static function zero(string $currency): self
    {
        return new self(0, strtoupper($currency));
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor - $other->amountMinor, $this->currency);
    }

    public function multiply(int $factor): self
    {
        return new self($this->amountMinor * $factor, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amountMinor === 0;
    }

    /**
     * Simple, locale-agnostic display formatting. Real currency-aware
     * formatting (locale, symbol placement, zero-decimal currencies like
     * JPY) is a Phase 8+ polish item — flagged rather than silently wrong.
     */
    public function format(): string
    {
        return sprintf('%s %s', $this->currency, number_format($this->amountMinor / 100, 2));
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
    }
}