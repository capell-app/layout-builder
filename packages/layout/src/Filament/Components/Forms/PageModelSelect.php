<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Components\Forms;

use Capell\Core\Data\PageData;
use Capell\Core\Facades\CapellCore;
use Filament\Forms\Components\Select;

class PageModelSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout::form.page_model'))
            ->options(
                fn (): array => collect(CapellCore::getPageTypes())
                    ->mapWithKeys(fn (PageData $type): array => [
                        $type->type => $type->model,
                    ])
                    ->all(),
            );
    }

    public static function getDefaultName(): ?string
    {
        return 'page_model';
    }
}
