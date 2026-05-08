<?php

declare(strict_types=1);

namespace Capell\AccessGate\Enums;

enum TokenPolicy: string
{
    case SingleActiveBrowserToken = 'single_active_browser_token';
    case MultipleBrowserTokens = 'multiple_browser_tokens';
}
