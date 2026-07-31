<?php

namespace Modules\Payments\DTOs;

final readonly class RefundResult
{
    public function __construct(
        public string $providerReference,
        public string $status,
    ) {}
}