<?php

declare(strict_types=1);

namespace Capell\ContentSections\Filament\Resources\Sections\Pages;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\ContentSections\Actions\MutateContentDataBeforeFillAction;
use Capell\ContentSections\Enums\ResourceEnum;
use Capell\ContentSections\Filament\Resources\Sections\SectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSection extends CreateRecord
{
    /** @return class-string<SectionResource> */
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(ResourceEnum::Section);
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $this->form->fill(MutateContentDataBeforeFillAction::run($this->data));

        $this->callHook('afterFill');
    }
}
