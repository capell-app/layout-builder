<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Bridges;

use Capell\Admin\Contracts\Bridges\AdminBridge;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\AgentBridge\Extenders\AgentBridgeUserSchemaExtender;

final class AgentBridgeAdminBridge implements AdminBridge
{
    public function isEnabled(AdminBridgeContextData $context): bool
    {
        return true;
    }

    public function register(AdminBridgeRegistrar $registrar, AdminBridgeContextData $context): void
    {
        $registrar->schemaExtender(AgentBridgeUserSchemaExtender::class, UserSchemaExtender::TAG);
    }
}
