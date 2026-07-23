<?php

namespace App\Support\Money;

use InvalidArgumentException;

readonly class Money
{
    public function __construct(
        public int $amountMinor,
        public string $currency = 'USD'
    ) {}

    public static function fromMajor(float|int $amount, string $currency = 'USD'): self
    {
        return new self((int) round($amount * 100), strtoupper($currency));
    }

    public static function fromMinor(int $amountMinor, string $currency = 'USD'): self
    {
        return new self($amountMinor, strtoupper($currency));
    }

    public function toMajor(): float
    {
        return $this->amountMinor / 100;
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->amountMinor - $other->amountMinor, $this->currency);
    }

    public function multiply(float|int $multiplier): self
    {
        return new self((int) round($this->amountMinor * $multiplier), $this->currency);
    }

    public function format(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'PHP' => '₱',
        ];

        $symbol = $symbols[strtoupper($this->currency)] ?? $this->currency . ' ';

        return $symbol . number_format($this->toMajor(), 2);
    }

    private function ensureSameCurrency(self $other): void
    {
        if (strtoupper($this->currency) !== strtoupper($other->currency)) {
            throw new InvalidArgumentException("Cannot perform operation on different currencies ({$this->currency} vs {$other->currency}).");
        }
    }
}