<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Tables\Columns\Content;

use Awcodes\BadgeableColumn\Components\Badge;
use Capell\Admin\Filament\Components\Tables\Columns\BadgeableColumn;
use Capell\Layout\Models\Content;

class ContentNameColumn extends BadgeableColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->searchable()
            ->sortable()
            ->weight('semibold')
            ->description(function (Content $record): ?string {
                $ancestors = $record->ancestors()->get();

                if ($ancestors->isEmpty()) {
                    return null;
                }

                return '» '.$ancestors->pluck('name')->join(' » ');
            })
            ->suffixBadges([
                Badge::make('children')
                    ->label(
                        fn (Content $record) => __(
                            'capell-admin::generic.total_children',
                            ['total' => $this->getChildCount($record)]
                        )
                    )
                    ->color('gray')
                    ->visible(fn (Content $record): bool => (bool) $this->getChildCount($record)),
            ]);
    }

    private function getChildCount(Content $record): int
    {
        if ($record->getAttributeValue('children_count') === null) {
            $record->loadCount('children');
        }

        return $record->children_count;
    }
}
