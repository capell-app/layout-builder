<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\PublishDates;
use Capell\Layout\Models\Content;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\HtmlString;

class ContentPublishSection extends Section
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->heading(__('capell-admin::generic.publish_settings'))
            ->icon(fn (Get $get, ?Content $record): string => match (true) {
                $get('publish_from') && Carbon::parse($get('publish_from'))->isFuture(),
                $get('publish_to') && Carbon::parse($get('publish_to'))->isPast() => 'heroicon-c-eye-slash',
                default => 'heroicon-c-eye',
            })
            ->iconColor(fn (Get $get, ?Content $record): string => match (true) {
                $get('publish_from') && Carbon::parse($get('publish_from'))->isFuture(),
                $get('publish_to') && Carbon::parse($get('publish_to'))->isPast() => 'warning',
                default => 'success',
            })
            ->collapsible()
            ->collapsed(function (Get $get): bool {
                $publishFrom = $get('publish_from');
                if ($publishFrom && Carbon::parse($publishFrom)->isFuture()) {
                    return false;
                }

                $publishTo = $get('publish_to');

                return ! $publishTo || Carbon::parse($publishTo)->isPast();
            })
            ->schema(function (?Content $record): array {
                if (! $record instanceof Content) {
                    return [];
                }

                return [
                    $this->modifiedDatePlaceholder(),
                    PublishDates::make(),
                ];
            })
            ->footerActions([
                $this->unpublishAction(),
                $this->revisionsAction(),
            ]);
    }

    private function countDrafts(?Content $record): int
    {
        if (! $record instanceof Content) {
            return 0;
        }

        if ($record->getAttributeValue('revisions_count') === null) {
            $record->loadCount(['revisions']);
        }

        return $record->revisions_count;
    }

    private function modifiedDatePlaceholder(): TextEntry
    {
        return TextEntry::make('modified-date')
            ->label(__('capell-admin::generic.last_updated'))
            ->hiddenLabel()
            ->visibleOn('edit')
            ->state(function (?Content $record): ?HtmlString {
                $contents = [
                    __('capell-admin::generic.created_by_at', [
                        'name' => $record->creator?->name,
                        'date' => $record->created_at?->diffForHumans(),
                    ]),
                ];

                if ($record->created_at->midDay()->notEqualTo($record->updated_at->midDay())) {
                    $contents[] = __('capell-admin::generic.updated_by_at', [
                        'name' => $record->editor?->name,
                        'date' => $record->updated_at?->diffForHumans(),
                    ]);
                }

                if ($record->isPublished()) {
                    $contents[] = __('capell-admin::generic.published_by_at', [
                        'name' => $record->publisher?->name,
                        'date' => $record->published_at?->diffForHumans(),
                    ]);
                }

                return new HtmlString('<p class="leading-tight">'.implode('</p><p class="leading-tight mt-4">', $contents).'</p>');
            });
    }

    private function revisionsAction(): Action
    {
        return Action::make('revisions')
            ->label(__('capell-admin::button.draft_revisions'))
            ->modal()
            ->badge(fn (?Content $record): int => $this->countDrafts($record))
            ->badgeColor('info')
            ->color('info')
            ->outlined()
            ->icon('heroicon-o-rectangle-stack')
            ->size(Size::Small)
            ->visible(fn (?Content $record): bool => $this->countDrafts($record) > 1)
            ->schema(
                fn (Schema $schema, ?Content $record): Schema => $schema->record(
                    $record->load([
                        'revisions' => fn (BuilderContract $query) => $query->orderByRaw(
                            'CASE WHEN `is_published` THEN 1 WHEN `is_current` THEN 2 ELSE 3 END, `updated_at` DESC'
                        ),
                        'revisions.translation',
                        'revisions.publisher',
                    ])
                )
                    ->schema([
                        RepeatableEntry::make('revisions')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('capell-admin::form.name'))
                                    ->hiddenLabel()
                                    ->weight(FontWeight::SemiBold)
                                    ->suffix(
                                        fn (Content $record): ?string => match (true) {
                                            $record->isCurrent() => ' ('.__('capell-admin::generic.latest').')',
                                            $record->isPublished() => ' ('.__('capell-admin::generic.published').')',
                                            default => null,
                                        }
                                    ),
                                TextEntry::make('translation.content')
                                    ->label(__('capell-admin::form.content'))
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))
                                    ->lineClamp(2),
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('publisher.name')
                                            ->visible(fn (Content $record): bool => $record->isPublished()),
                                        TextEntry::make('published_at')
                                            ->visible(fn (Content $record): bool => $record->isPublished())
                                            ->dateTime(),
                                        TextEntry::make('updated_at')
                                            ->since()
                                            ->dateTimeTooltip(),
                                    ]),
                                Actions::make([
                                    Action::make('view')
                                        ->label(__('capell-admin::button.view'))
                                        ->icon('heroicon-m-arrow-top-right-on-square')
                                        ->size(Size::Small)
                                        ->url(
                                            function (Content $record): string {
                                                $record->loadMissing('pageUrl.siteDomain');

                                                return $record->pageUrl->full_url;
                                            },
                                            shouldOpenInNewTab: true
                                        ),
                                    Action::make('edit')
                                        ->label(__('capell-admin::button.edit'))
                                        ->icon('heroicon-m-pencil-square')
                                        ->size(Size::Small)
                                        ->disabled(fn (Content $record, $livewire): bool => $record->is($livewire->getRecord()))
                                        ->url(
                                            fn (Content $record, $livewire): string => $livewire::getResource()::getUrl('edit', ['record' => $record])
                                        ),
                                    Action::make('delete')
                                        ->label(__('capell-admin::button.delete'))
                                        ->icon('heroicon-m-trash')
                                        ->color('danger')
                                        ->size(Size::Small)
                                        ->disabled(fn (Content $record, $livewire): bool => $record->is($livewire->getRecord()))
                                        ->requiresConfirmation(),
                                ])
                                    ->alignRight(),
                            ]),
                    ])
            )
            ->modalSubmitAction(false);
    }

    private function unpublishAction(): Action
    {
        return Action::make('unpublish')
            ->label(__('capell-admin::button.unpublish'))
            ->icon('heroicon-m-shield-exclamation')
            ->color('danger')
            ->outlined()
            ->size(Size::Small)
            ->visible(fn (?Content $record): bool => (bool) $record?->isPublished())
            ->requiresConfirmation()
            ->modalDescription(__('capell-admin::message.unpublish_page_confirmation'))
            ->action(function (?Content $record): void {
                $record->unpublish();
            });
    }
}
