<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Filament\Resources\Layouts\Pages\EditLayout;
use Filament\Support\Enums\Width;

it('uses the full admin content width for the visual layout workspace', function (): void {
    expect((new EditLayout)->getMaxContentWidth())->toBe(Width::Full);
});
