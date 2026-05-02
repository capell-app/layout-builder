<?php

declare(strict_types=1);

namespace Capell\Workspaces\Extenders;

use Capell\Admin\Contracts\Extenders\PageResourcePageExtender;
use Capell\Workspaces\Filament\Resources\Pages\Pages\PageVersionHistoryPage;

class WorkspacesPageResourcePageExtender implements PageResourcePageExtender
{
    /** @return array<string, mixed> */
    public function getPages(): array
    {
        return [
            'history' => PageVersionHistoryPage::route('/{record}/history'),
        ];
    }
}
