<?php

declare(strict_types=1);

namespace Capell\AgentBridge\Support\AdminTools;

use Capell\Admin\Contracts\AdminTools\AdminToolItem;
use Capell\AgentBridge\Filament\Pages\CapellAgentBridgePromptBuilderPage;
use Illuminate\Support\Facades\Blade;

final class PromptBuilderAdminTool implements AdminToolItem
{
    public function render(): string
    {
        return Blade::render(
            <<<'BLADE'
                <a
                    class="fi-dropdown-list-item fi-dropdown-list-item-color-gray flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm outline-none transition-colors duration-75 hover:bg-gray-50 focus:bg-gray-50 dark:hover:bg-white/5 dark:focus:bg-white/5"
                    href="{{ $url }}"
                >
                    @svg('heroicon-o-chat-bubble-left-right', 'fi-dropdown-list-item-icon h-5 w-5 text-gray-400 dark:text-gray-500')
                    {{ __('capell-agent-bridge::admin.prompt_builder_tool') }}
                </a>
            BLADE,
            ['url' => CapellAgentBridgePromptBuilderPage::getUrl()],
        );
    }
}
