<?php

declare(strict_types=1);

namespace Modules\Platform\app\Filament\Resources;

use DomainException;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Modules\Platform\app\Filament\Resources\MerchantApplicationResource\Pages\ListMerchantApplications;
use Modules\Platform\app\Filament\Resources\MerchantApplicationResource\Pages\ViewMerchantApplication;
use Modules\Platform\app\Models\MerchantApplication;
use Modules\Platform\app\Services\ApplicationReviewService;

final class MerchantApplicationResource extends Resource
{
    protected static ?string $model = MerchantApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Merchant Applications';

    protected static ?string $recordTitleAttribute = 'business_name';

    /**
     * List/View only — applications are never created or hand-edited
     * from the admin panel, only decided on via the actions below.
     * No `form()` schema is defined for that reason; getPages() omits
     * create/edit routes entirely.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('business_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title())
                    ->badge(),

                Tables\Columns\TextColumn::make('applicant.name')
                    ->label('Applicant')
                    ->searchable(),

                Tables\Columns\TextColumn::make('applicant.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        MerchantApplication::STATUS_SUBMITTED => 'gray',
                        MerchantApplication::STATUS_UNDER_REVIEW => 'info',
                        MerchantApplication::STATUS_INFO_REQUESTED => 'warning',
                        MerchantApplication::STATUS_APPROVED => 'success',
                        MerchantApplication::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('decided_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        MerchantApplication::STATUS_SUBMITTED => 'Submitted',
                        MerchantApplication::STATUS_UNDER_REVIEW => 'Under review',
                        MerchantApplication::STATUS_INFO_REQUESTED => 'Info requested',
                        MerchantApplication::STATUS_APPROVED => 'Approved',
                        MerchantApplication::STATUS_REJECTED => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                self::approveAction(),
                self::rejectAction(),
                self::requestInfoAction(),
            ])
            ->bulkActions([]);
        // No bulk approve/reject: every decision requires an
        // individual reviewer note per §7.2 (application_reviews.notes),
        // and batching would encourage rubber-stamping.
    }

    /**
     * Only visible while the application is genuinely awaiting an
     * admin decision — mirrors ApplicationReviewService::assertReviewable().
     * Kept as a single source of truth would be even better (a shared
     * `MerchantApplication::isAwaitingAdminDecision()` helper); left
     * inline here for now to keep this task's diff self-contained.
     */
    private static function isReviewable(MerchantApplication $record): bool
    {
        return in_array($record->status, [
            MerchantApplication::STATUS_SUBMITTED,
            MerchantApplication::STATUS_UNDER_REVIEW,
        ], true);
    }

    private static function approveAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('This approves the application and allows the merchant to begin store setup. This cannot be undone from here.')
            ->form([
                Forms\Components\Textarea::make('notes')
                    ->label('Internal note (optional)')
                    ->maxLength(2000),
            ])
            ->visible(fn (MerchantApplication $record): bool => self::isReviewable($record))
            ->authorize(fn (MerchantApplication $record): bool => Auth::user()?->can('review', $record) ?? false)
            ->action(function (MerchantApplication $record, array $data): void {
                self::handle(
                    fn (ApplicationReviewService $service) => $service->approve($record, Auth::user(), $data['notes'] ?? null),
                    successTitle: 'Application approved',
                    successBody: "{$record->business_name} has been approved.",
                );
            });
    }

    private static function rejectAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->form([
                Forms\Components\Textarea::make('reason')
                    ->label('Rejection reason')
                    ->helperText('Shown to the applicant — be clear and constructive.')
                    ->required()
                    ->maxLength(2000),
            ])
            ->visible(fn (MerchantApplication $record): bool => self::isReviewable($record))
            ->authorize(fn (MerchantApplication $record): bool => Auth::user()?->can('review', $record) ?? false)
            ->action(function (MerchantApplication $record, array $data): void {
                self::handle(
                    fn (ApplicationReviewService $service) => $service->reject($record, Auth::user(), $data['reason']),
                    successTitle: 'Application rejected',
                    successBody: "{$record->business_name} has been rejected.",
                );
            });
    }

    private static function requestInfoAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('request_info')
            ->label('Request info')
            ->icon('heroicon-o-question-mark-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->form([
                Forms\Components\Textarea::make('notes')
                    ->label('What do you need from the applicant?')
                    ->required()
                    ->maxLength(2000),
            ])
            ->visible(fn (MerchantApplication $record): bool => self::isReviewable($record))
            ->authorize(fn (MerchantApplication $record): bool => Auth::user()?->can('review', $record) ?? false)
            ->action(function (MerchantApplication $record, array $data): void {
                self::handle(
                    fn (ApplicationReviewService $service) => $service->requestInfo($record, Auth::user(), $data['notes']),
                    successTitle: 'More info requested',
                    successBody: "{$record->business_name} has been notified.",
                );
            });
    }

    /**
     * Shared error handling for all three actions: the service throws
     * DomainException on an illegal transition (e.g. two admins
     * racing to decide the same application from two open tabs) —
     * surfaced as a Filament notification instead of a 500.
     */
    private static function handle(callable $callback, string $successTitle, string $successBody): void
    {
        try {
            $callback(app(ApplicationReviewService::class));

            NotificationFacade::make()
                ->title($successTitle)
                ->body($successBody)
                ->success()
                ->send();
        } catch (DomainException $exception) {
            NotificationFacade::make()
                ->title('Could not update application')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchantApplications::route('/'),
            'view' => ViewMerchantApplication::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['applicant']);
    }
}