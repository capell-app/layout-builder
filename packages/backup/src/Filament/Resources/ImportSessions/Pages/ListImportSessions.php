<?php

declare(strict_types=1);

namespace Capell\Backup\Filament\Resources\ImportSessions\Pages;

use Capell\Backup\Filament\Resources\ImportSessions\ImportSessionResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Override;

class ListImportSessions extends ListRecords
{
    /** @return class-string<ImportSessionResource> */
    #[Override]
    public static function getResource(): string
    {
        return ImportSessionResource::class;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::hints.import_sessions');
    }

    #[Override]
    public function getSubNavigation(): array
    {
        return ImportSessionResource::getSubNavigation();
    }

    protected function getActions(): array
    {
        return [];
    }
}
