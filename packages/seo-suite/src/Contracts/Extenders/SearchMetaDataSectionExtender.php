<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Contracts\Extenders;

use Filament\Actions\Action;
use Filament\Schemas\Components\Section;

interface SearchMetaDataSectionExtender
{
    /** @var string */
    public const TAG = 'capell-admin:search-meta-data-section';

    /**
     * @return array<int, Action>
     */
    public function headerActions(Section $component): array;
}
