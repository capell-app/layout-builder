<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Layouts\Pages;

use Filament\Support\Enums\Width;
use Override;

class EditLayout extends \Capell\Admin\Filament\Resources\Layouts\Pages\EditLayout
{
    #[Override]
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
