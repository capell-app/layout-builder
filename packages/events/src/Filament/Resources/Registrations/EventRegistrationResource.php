<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Resources\Registrations;

use BackedEnum;
use Capell\Events\Filament\Resources\Registrations\Pages\ManageEventRegistrations;
use Capell\Events\Models\EventRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class EventRegistrationResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    #[Override]
    public static function getModel(): string
    {
        return EventRegistration::class;
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-events::generic.event_registrations');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_content');
    }

    public static function getNavigationParentItem(): ?string
    {
        return (string) __('capell-events::generic.events');
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('capell-events::table.name'))->searchable(),
            TextColumn::make('email')->label(__('capell-events::table.email'))->searchable(),
            TextColumn::make('occurrence.event.name')->label(__('capell-events::table.event')),
            TextColumn::make('status')->label(__('capell-events::table.status'))->badge(),
            TextColumn::make('quantity')->label(__('capell-events::table.quantity')),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEventRegistrations::route('/'),
        ];
    }
}
