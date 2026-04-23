<?php

declare(strict_types=1);

namespace Capell\SeoTools\Filament\Components\Forms;

use Capell\SeoTools\Contracts\Schemas\SearchMetaDataSectionExtenderResolverInterface;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

class SearchMetaDataSection extends Section
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->heading(__('capell-admin::generic.search_meta_data'))
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->columns()
            ->columnSpanFull()
            ->headerActions($this->resolveHeaderActions());
    }

    private function resolveHeaderActions(): array
    {
        return resolve(SearchMetaDataSectionExtenderResolverInterface::class)->resolveHeaderActions($this);
    }
}
