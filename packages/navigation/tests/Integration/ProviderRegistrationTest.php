<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Navigation\Models\Navigation;

it('registers the navigation model with Capell core', function (): void {
    expect(CapellCore::getModels())->toContain(Navigation::class);
});
