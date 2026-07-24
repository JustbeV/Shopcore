<?php

declare(strict_types=1);

namespace Modules\Identity\app\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Identity\app\Models\StoreStaff;

final class StaffInvited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly StoreStaff $staff
    ) {}
}