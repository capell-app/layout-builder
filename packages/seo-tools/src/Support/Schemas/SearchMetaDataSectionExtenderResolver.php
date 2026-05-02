<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\Schemas;

use Capell\SeoTools\Contracts\Extenders\SearchMetaDataSectionExtender;
use Capell\SeoTools\Contracts\Schemas\SearchMetaDataSectionExtenderResolverInterface;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;

class SearchMetaDataSectionExtenderResolver implements SearchMetaDataSectionExtenderResolverInterface
{
    /**
     * @return array<int, Action>
     */
    public function resolveHeaderActions(Section $section): array
    {
        $actions = [];

        foreach ($this->getExtenders() as $extender) {
            $actions = [
                ...$actions,
                ...$extender->headerActions($section),
            ];
        }

        return $actions;
    }

    /**
     * @return iterable<SearchMetaDataSectionExtender>
     */
    private function getExtenders(): iterable
    {
        return app()->tagged(SearchMetaDataSectionExtender::TAG);
    }
}
