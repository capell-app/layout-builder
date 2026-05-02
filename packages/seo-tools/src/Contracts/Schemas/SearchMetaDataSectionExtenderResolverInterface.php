<?php

declare(strict_types=1);

namespace Capell\SeoTools\Contracts\Schemas;

use Filament\Actions\Action;
use Filament\Schemas\Components\Section;

interface SearchMetaDataSectionExtenderResolverInterface
{
    /**
     * @return array<int, Action>
     */
    public function resolveHeaderActions(Section $section): array;
}
