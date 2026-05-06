<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Sections\Pages;

use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\LayoutBuilder\Actions\MutateContentDataBeforeFillAction;
use Capell\LayoutBuilder\Enums\ResourceEnum;
use Capell\LayoutBuilder\Filament\Resources\Sections\SectionResource;
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
