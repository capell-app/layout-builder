<?php

declare(strict_types=1);

namespace Capell\Events\Filament\Resources\Occurrences;

use BackedEnum;
use Capell\Events\Filament\Resources\Occurrences\Pages\ManageEventOccurrences;
use Capell\Events\Models\EventOccurrence;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

class EventOccurrenceResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    #[Override]
    public static function getModel(): string
    {
        return EventOccurrence::class;
    }

    public static function getNavigationLabel(): string
    {
        return __('capell-events::generic.event_occurrences');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_content');
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('event.name')->label(__('capell-events::table.event'))->searchable(),
            TextColumn::make('starts_at')->label(__('capell-events::table.starts_at'))->dateTime()->sortable(),
            TextColumn::make('status')->label(__('capell-events::table.status'))->badge(),
            TextColumn::make('registration_count')->label(__('capell-events::table.registrations')),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEventOccurrences::route('/'),
        ];
    }
}
