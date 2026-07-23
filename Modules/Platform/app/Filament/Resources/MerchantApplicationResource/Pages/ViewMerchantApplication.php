<?php

declare(strict_types=1);

namespace Modules\Platform\app\Filament\Resources\MerchantApplicationResource\Pages;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Modules\Platform\app\Filament\Resources\MerchantApplicationResource;
use Modules\Platform\app\Models\MerchantApplication;

final class ViewMerchantApplication extends ViewRecord
{
    protected static string $resource = MerchantApplicationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Application')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('business_name')->label('Business name'),
                        TextEntry::make('business_type')
                            ->label('Business type')
                            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                        TextEntry::make('applicant.name')->label('Applicant'),
                        TextEntry::make('applicant.email')->label('Applicant email'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                        TextEntry::make('submitted_at')->dateTime(),
                        TextEntry::make('decided_at')->dateTime()->placeholder('—'),
                        TextEntry::make('rejection_reason')
                            ->placeholder('—')
                            ->visible(fn (MerchantApplication $record): bool => filled($record->rejection_reason)),
                    ]),

                Section::make('Submitted business details')
                    ->schema([
                        KeyValueEntry::make('metadata')
                            ->label('')
                            ->keyLabel('Field')
                            ->valueLabel('Value'),
                    ]),

                Section::make('Review history')
                    ->schema([
                        RepeatableEntry::make('reviews')
                            ->label('')
                            ->schema([
                                TextEntry::make('reviewer.name')->label('Reviewer'),
                                TextEntry::make('action')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                                TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                                TextEntry::make('created_at')->dateTime(),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn (MerchantApplication $record): bool => $record->reviews()->exists()),
            ]);
    }
}