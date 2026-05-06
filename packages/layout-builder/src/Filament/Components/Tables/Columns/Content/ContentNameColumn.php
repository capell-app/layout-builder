<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Tables\Columns\Content;

use Awcodes\BadgeableColumn\Components\Badge;
use Capell\Admin\Filament\Components\Tables\Columns\BadgeableColumn;
use Capell\LayoutBuilder\Models\Section;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ContentNameColumn extends BadgeableColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()
            ->sortable()
            ->wrap()
            ->weight(FontWeight::Medium)
            ->description(function (Section $record): ?HtmlString {
                $ancestors = $record->ancestors()->get();

                if ($ancestors->isEmpty()) {
                    return null;
                }

                return new HtmlString($ancestors->pluck('name')->join(' &raquo; '));
            })
            ->suffixBadges([
                Badge::make('children')
                    ->label(
                        fn (Section $record): string|array|null => __(
                            'capell-admin::generic.total_children',
                            ['total' => $this->getChildCount($record)],
                        ),
                    )
                    ->color('gray')
                    ->visible(fn (Section $record): bool => (bool) $this->getChildCount($record)),
            ]);
    }

    private function getChildCount(Section $record): int
    {
        if ($record->getAttributeValue('children_count') === null) {
            $record->loadCount('children');
        }

        return $record->children_count;
    }
}
