<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Exceptions;

use RuntimeException;

class EmailStudioSendingException extends RuntimeException
{
    public static function profileNotFound(string $siteScopeKey): self
    {
        return new self("No email profile is available for site scope [{$siteScopeKey}].");
    }

    public static function templateNotFound(string $templateKey, string $siteScopeKey): self
    {
        return new self("No approved email template [{$templateKey}] is available for site scope [{$siteScopeKey}].");
    }

    public static function variantNotFound(string $templateKey, string $siteScopeKey): self
    {
        return new self("No active email template variant [{$templateKey}] is available for site scope [{$siteScopeKey}].");
    }

    public static function noRecipients(string $templateKey): self
    {
        return new self("No recipients were provided for email template [{$templateKey}].");
    }

    public static function providerNotRegistered(string $provider): self
    {
        return new self("No email provider adapter is registered for [{$provider}].");
    }
}
