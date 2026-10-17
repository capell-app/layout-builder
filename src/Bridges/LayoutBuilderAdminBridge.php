<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Bridges;

use Capell\Admin\Contracts\Bridges\AdminBridge;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\Core\Facades\CapellCore;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Support\LayoutBuilderAdminRegistrar;
use Override;

final class LayoutBuilderAdminBridge implements AdminBridge
{
    #[Override]
    public function isEnabled(AdminBridgeContextData $context): bool
    {
        return CapellCore::isPackageInstalled(LayoutBuilderServiceProvider::$packageName);
    }

    #[Override]
    public function register(AdminBridgeRegistrar $registrar, AdminBridgeContextData $context): void
    {
        // Defer the whole contribution bucket until Admin has prepared its built-ins.
        app()->make(LayoutBuilderAdminRegistrar::class)->register();
    }
}
