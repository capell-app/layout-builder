<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms\Content;

use Capell\Admin\Filament\Components\Forms\PublishDates;
use Capell\Layout\Models\Content;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Infolists;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\HtmlString;

class ContentPublishSection extends Forms\Components\Section
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->heading(__('capell-admin::generic.publish_settings'))
            ->icon(fn (Forms\Get $get, ?Content $record): string => match (true) {
                $get('publish_from') && Carbon::parse($get('publish_from'))->isFuture(),
                $get('publish_to') && Carbon::parse($get('publish_to'))->isPast() => 'heroicon-c-eye-slash',
                default => 'heroicon-c-eye',
            })
            ->iconColor(fn (Forms\Get $get, ?Content $record): string => match (true) {
                $get('publish_from') && Carbon::parse($get('publish_from'))->isFuture(),
                $get('publish_to') && Carbon::parse($get('publish_to'))->isPast() => 'warning',
                default => 'success',
            })
            ->collapsible()
            ->collapsed(function (Forms\Get $get): bool {
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

    private function modifiedDatePlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('modified-date')
            ->label(__('capell-admin::generic.last_updated'))
            ->hiddenLabel()
            ->muted()
            ->visibleOn('edit')
            ->content(function (?Content $record): ?HtmlString {
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

    private function revisionsAction(): Forms\Components\Actions\Action
    {
        return Forms\Components\Actions\Action::make('revisions')
            ->label(__('capell-admin::button.draft_revisions'))
            ->modal()
            ->badge(fn (?Content $record): int => $this->countDrafts($record))
            ->badgeColor('info')
            ->color('info')
            ->outlined()
            ->icon('heroicon-o-rectangle-stack')
            ->size(ActionSize::Small)
            ->visible(fn (?Content $record): bool => $this->countDrafts($record) > 1)
            ->infolist(
                fn (Infolists\Infolist $infolist, ?Content $record): Infolists\Infolist => $infolist->record(
                    $record->load([
                        'revisions' => fn (BuilderContract $query) => $query->orderByRaw(
                            'CASE WHEN `is_published` THEN 1 WHEN `is_current` THEN 2 ELSE 3 END, `updated_at` DESC'
                        ),
                        'revisions.translation',
                        'revisions.publisher',
                    ])
                )
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('revisions')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
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
                                Infolists\Components\TextEntry::make('translation.content')
                                    ->label(__('capell-admin::form.content'))
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))
                                    ->lineClamp(2),
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('publisher.name')
                                            ->visible(fn (Content $record): bool => $record->isPublished()),
                                        Infolists\Components\TextEntry::make('published_at')
                                            ->visible(fn (Content $record): bool => $record->isPublished())
                                            ->dateTime(),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->since()
                                            ->dateTimeTooltip(),
                                    ]),
                                Infolists\Components\Actions::make([
                                    Infolists\Components\Actions\Action::make('view')
                                        ->label(__('capell-admin::button.view'))
                                        ->icon('heroicon-m-arrow-top-right-on-square')
                                        ->size(ActionSize::Small)
                                        ->url(
                                            function (Content $record): string {
                                                $record->loadMissing('pageUrl.siteDomain');

                                                return $record->pageUrl->full_url;
                                            },
                                            shouldOpenInNewTab: true
                                        ),
                                    Infolists\Components\Actions\Action::make('edit')
                                        ->label(__('capell-admin::button.edit'))
                                        ->icon('heroicon-m-pencil-square')
                                        ->size(ActionSize::Small)
                                        ->disabled(fn (Content $record, $livewire): bool => $record->is($livewire->getRecord()))
                                        ->url(
                                            fn (Content $record, $livewire): string => $livewire::getResource()::getUrl('edit', ['record' => $record])
                                        ),
                                    Infolists\Components\Actions\Action::make('delete')
                                        ->label(__('capell-admin::button.delete'))
                                        ->icon('heroicon-m-trash')
                                        ->color('danger')
                                        ->size(ActionSize::Small)
                                        ->disabled(fn (Content $record, $livewire): bool => $record->is($livewire->getRecord()))
                                        ->requiresConfirmation(),
                                ])
                                    ->alignRight(),
                            ]),
                    ])
            )
            ->modalSubmitAction(false);
    }

    private function unpublishAction(): Forms\Components\Actions\Action
    {
        return Forms\Components\Actions\Action::make('unpublish')
            ->label(__('capell-admin::button.unpublish'))
            ->icon('heroicon-m-shield-exclamation')
            ->color('danger')
            ->outlined()
            ->size(ActionSize::Small)
            ->visible(fn (?Content $record): bool => (bool) $record?->isPublished())
            ->requiresConfirmation()
            ->modalDescription(__('capell-admin::message.unpublish_page_confirmation'))
            ->action(function (?Content $record): void {
                $record->unpublish();
            });
    }
}
