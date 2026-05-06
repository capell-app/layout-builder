<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Extenders;

use Capell\Admin\Contracts\Extenders\PageResourcePageExtender;
use Capell\PublishingStudio\Filament\Resources\Pages\Pages\PageVersionHistoryPage;

class PublishingStudioPageResourcePageExtender implements PageResourcePageExtender
{
    /** @return array<string, mixed> */
    public function getPages(): array
    {
        return [
            'history' => PageVersionHistoryPage::route('/{record}/history'),
        ];
    }
}
