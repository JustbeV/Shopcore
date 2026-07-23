<?php

declare(strict_types=1);

namespace Modules\Platform\app\Filament\Resources\MerchantApplicationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Platform\app\Filament\Resources\MerchantApplicationResource;

final class ListMerchantApplications extends ListRecords
{
    protected static string $resource = MerchantApplicationResource::class;

    /**
     * No "Create" header action — applications only ever originate
     * from a merchant's own submission (Task 1's API endpoint), never
     * from the admin panel.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}