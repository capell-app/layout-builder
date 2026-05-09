<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Exceptions;

use RuntimeException;

class EmailTemplateRenderingException extends RuntimeException
{
    /**
     * @param  array<int, string>  $variables
     */
    public static function missingVariables(array $variables): self
    {
        return new self(sprintf(
            'Cannot render email template because these variables are missing: %s',
            implode(', ', $variables),
        ));
    }
}
