<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Contracts\Extensions\ExtensionContribution;
use Capell\Core\Contracts\Extensions\RegistersExtensionAdminResource;
use Capell\Core\Contracts\Extensions\RegistersExtensionAsset;
use Capell\LayoutBuilder\Filament\Resources\Layouts\LayoutResource;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Illuminate\Contracts\Container\Container;

final class LayoutBuilderAdminRegistrar implements ExtensionContribution, RegistersExtensionAdminResource, RegistersExtensionAsset
{
    public const REGISTRATION_FLAG = 'capell.layout_builder.admin_registered';

    private const LEGACY_LAYOUT_RESOURCE = 'Capell\\Admin\\LayoutBuilder\\Filament\\Resources\\Layouts\\LayoutResource';

    private const LEGACY_WIDGET_RESOURCE = 'Capell\\Admin\\LayoutBuilder\\Filament\\Resources\\Widgets\\WidgetResource';

    public function __construct(private readonly Container $app) {}

    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
    }

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

        return $this->containsResource($resources, LayoutResource::class, self::LEGACY_LAYOUT_RESOURCE)
            && $this->containsResource($resources, WidgetResource::class, self::LEGACY_WIDGET_RESOURCE);
    }

    /**
     * @param  array<int, class-string>  $resources
     */
    private function containsResource(array $resources, string ...$classes): bool
    {
        foreach ($classes as $class) {
            if (in_array($class, $resources, true)) {
                return true;
            }
        }

        return false;
    }
}
