<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Actions;

use Capell\EmailStudio\Models\EmailTemplateRegistration;
use Lorisleiva\Actions\Concerns\AsAction;

class RegisterEmailTemplateAction
{
    use AsAction;

    /**
     * @param  array<int, string>  $variables
     */
    public function handle(
        string $key,
        string $name,
        array $variables,
        ?string $description = null,
        string $packageName = 'capell-app/email-studio',
        ?int $siteId = null,
        string $siteScopeKey = 'global',
    ): EmailTemplateRegistration {
        /** @var EmailTemplateRegistration $registration */
        $registration = EmailTemplateRegistration::query()->updateOrCreate(
            [
                'site_scope_key' => $siteScopeKey,
                'package_name' => $packageName,
                'template_key' => $key,
            ],
            [
                'site_id' => $siteId,
                'name' => $name,
                'description' => $description,
                'variables' => array_values($variables),
            ],
        );

        return $registration;
    }
}
