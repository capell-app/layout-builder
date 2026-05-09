<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Support;

use Capell\EmailStudio\Actions\RegisterEmailTemplateAction;
use Capell\EmailStudio\Models\EmailTemplateRegistration;

class EmailTemplateRegistry
{
    /**
     * @var array<int, array{key: string, name: string, variables: array<int, string>, description: string|null, packageName: string, siteId: int|null, siteScopeKey: string}>
     */
    private array $registrations = [];

    /**
     * @param  array<int, string>  $variables
     */
    public function register(
        string $key,
        string $name,
        array $variables,
        ?string $description = null,
        string $packageName = 'capell-app/email-studio',
        ?int $siteId = null,
        string $siteScopeKey = 'global',
    ): self {
        $this->registrations[] = [
            'key' => $key,
            'name' => $name,
            'variables' => array_values($variables),
            'description' => $description,
            'packageName' => $packageName,
            'siteId' => $siteId,
            'siteScopeKey' => $siteScopeKey,
        ];

        return $this;
    }

    /**
     * @return array<int, EmailTemplateRegistration>
     */
    public function persist(): array
    {
        return array_map(
            static fn (array $registration): EmailTemplateRegistration => RegisterEmailTemplateAction::run(...$registration),
            $this->registrations,
        );
    }
}
