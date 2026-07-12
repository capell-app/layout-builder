<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms;

use Filament\Forms\Components\Select;

class HeadingStyleSelect extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-layout-builder::form.heading_style'))
            ->options(self::styleOptions())
            ->hidden(count(self::styleOptions()) < 2);
    }

    /**
     * @return array<string, string>
     */
    public static function styleOptions(): array
    {
        return [
            'secondary' => __('capell-admin::generic.secondary'),
        ];
    }
}
