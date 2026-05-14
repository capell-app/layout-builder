<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\Admin\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Illuminate\Contracts\Container\Container;

final class LayoutBuilderAdminRegistrar
{
    public const REGISTRATION_FLAG = 'capell.layout_builder.admin_registered';

    public function __construct(private readonly Container $app) {}

    public function register(): void
    {
        if ($this->isRegistered() && $this->hasRegisteredAdminSurface()) {
            return;
        }

        $adminRegistrar = 'Capell\\Admin\\LayoutBuilder\\Support\\LayoutBuilderAdminRegistrar';

        if (! class_exists($adminRegistrar)) {
            return;
        }

        $this->app->make($adminRegistrar)->register();

        if (! $this->isRegistered()) {
            $this->markRegistered();
        }
    }

    public function isRegistered(): bool
    {
        return $this->app->bound(self::REGISTRATION_FLAG)
            && $this->app->make(self::REGISTRATION_FLAG) === true;
    }

    public function markRegistered(): void
    {
        $this->app->instance(self::REGISTRATION_FLAG, true);
    }

    private function hasRegisteredAdminSurface(): bool
    {
        if (! class_exists(LayoutResource::class) || ! class_exists(WidgetResource::class)) {
            return false;
        }

        $resources = CapellAdmin::getAdminSurfaceRegistry()->resources();

        return in_array(LayoutResource::class, $resources, true)
            && in_array(WidgetResource::class, $resources, true);
    }
}
