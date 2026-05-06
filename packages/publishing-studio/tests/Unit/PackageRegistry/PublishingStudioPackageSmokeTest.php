<?php

declare(strict_types=1);

use Capell\PublishingStudio\Providers\PublishingStudioServiceProvider;

it('publishing-studio service provider class exists in new namespace', function (): void {
    expect(class_exists(PublishingStudioServiceProvider::class))->toBeTrue();
});
