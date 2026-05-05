<?php

declare(strict_types=1);

namespace Capell\Migrator\Filament\Resources\ImportSessions\Schemas;

use Capell\Migrator\Enums\ImportSessionStatus;
use Capell\Migrator\Models\ImportSession;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ImportSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('capell-admin::exchanger.session_overview'))
                ->columns(2)
                ->schema([
                    TextEntry::make('uuid')->label(__('capell-admin::exchanger.uuid')),
                    TextEntry::make('kind')
                        ->label(__('capell-admin::exchanger.kind'))
                        ->badge(),
                    TextEntry::make('status')
                        ->label(__('capell-admin::exchanger.status'))
                        ->badge()
                        ->color(fn (ImportSessionStatus $state): string => match ($state) {
                            ImportSessionStatus::Completed => 'success',
                            ImportSessionStatus::Failed => 'danger',
                            ImportSessionStatus::Running, ImportSessionStatus::Queued => 'warning',
                            default => 'gray',
                        }),
                    TextEntry::make('user.name')
                        ->label(__('capell-admin::exchanger.user'))
                        ->placeholder('—'),
                    TextEntry::make('source_filename')->label(__('capell-admin::exchanger.source_filename')),
                    TextEntry::make('failure_reason')
                        ->label(__('capell-admin::exchanger.failure_reason'))
                        ->columnSpanFull()
                        ->visible(fn (ImportSession $record): bool => $record->failure_reason !== null),
                ]),
            Section::make(__('capell-admin::exchanger.timeline'))
                ->schema([
                    ViewEntry::make('timeline')
                        ->view('capell-admin::components.exchanger.import-session-timeline'),
                ]),
            Section::make(__('capell-admin::exchanger.validation_report'))
                ->collapsible()
                ->schema([
                    ViewEntry::make('validation_results')
                        ->view('capell-admin::components.exchanger.import-session-validation'),
                ])
                ->visible(fn (ImportSession $record): bool => is_array($record->validation_results) && $record->validation_results !== []),
            Section::make(__('capell-admin::exchanger.result_summary'))
                ->collapsible()
                ->schema([
                    ViewEntry::make('result_summary')
                        ->view('capell-admin::components.exchanger.import-session-result'),
                ])
                ->visible(fn (ImportSession $record): bool => is_array($record->result_summary) && $record->result_summary !== []),
            Section::make(__('capell-admin::exchanger.page_decisions'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    ViewEntry::make('page_decisions')
                        ->view('capell-admin::components.exchanger.import-session-json')
                        ->state(fn (ImportSession $record): array => [
                            'payload' => $record->page_decisions,
                        ]),
                ]),
            Section::make(__('capell-admin::exchanger.relation_decisions'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    ViewEntry::make('relation_decisions')
                        ->view('capell-admin::components.exchanger.import-session-json')
                        ->state(fn (ImportSession $record): array => [
                            'payload' => $record->relation_decisions,
                        ]),
                ]),
            Section::make(__('capell-admin::exchanger.manifest'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    ViewEntry::make('manifest')
                        ->view('capell-admin::components.exchanger.import-session-json')
                        ->state(fn (ImportSession $record): array => [
                            'payload' => $record->manifest,
                        ]),
                ])
                ->visible(fn (ImportSession $record): bool => is_array($record->manifest) && $record->manifest !== []),
        ]);
    }
}
