<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Feature\Providers;

use Capell\Assistant\Support\AssistantModuleRegistry;
use Capell\Mosaic\Tests\MosaicTestCase;

final class MosaicAssistantModuleRegistrationTest extends MosaicTestCase
{
    public function test_it_skips_mosaic_assistant_module_when_assistant_is_not_installed(): void
    {
        $registry = $this->app->make(AssistantModuleRegistry::class);

        $this->assertSame([], $registry->modules());
    }
}
