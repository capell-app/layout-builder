<?php

declare(strict_types=1);

namespace Capell\Tags\Providers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Tags\Enums\ResourceEnum;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        CapellAdmin::registerResource(ResourceEnum::Tag->name, class: ResourceEnum::Tag->value);
    }

    public function boot(): void {}
}
