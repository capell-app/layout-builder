<?php

declare(strict_types=1);

use Capell\EmailStudio\Actions\ResolveEmailTemplateVariantAction;
use Capell\EmailStudio\Enums\EmailVariantStatus;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateVariant;

it('resolves an active variant from the requested site and locale before fallbacks', function (): void {
    $template = EmailTemplate::factory()->create();

    EmailTemplateVariant::factory()->for($template, 'template')->create([
        'site_id' => 99,
        'site_scope_key' => 'site:99',
        'locale' => 'en',
        'version' => 99,
        'subject' => 'Other site',
    ]);

    EmailTemplateVariant::factory()->for($template, 'template')->create([
        'site_id' => 12,
        'site_scope_key' => 'site:12',
        'locale' => null,
        'version' => 10,
        'subject' => 'Site fallback',
    ]);

    EmailTemplateVariant::factory()->for($template, 'template')->create([
        'site_id' => 12,
        'site_scope_key' => 'site:12',
        'locale' => 'en',
        'status' => EmailVariantStatus::Retired,
        'version' => 20,
        'subject' => 'Retired site variant',
    ]);

    $expectedVariant = EmailTemplateVariant::factory()->for($template, 'template')->create([
        'site_id' => 12,
        'site_scope_key' => 'site:12',
        'locale' => 'en',
        'version' => 1,
        'subject' => 'Expected site variant',
    ]);

    $resolvedVariant = ResolveEmailTemplateVariantAction::run($template, 'site:12', 'en');

    expect($resolvedVariant?->is($expectedVariant))->toBeTrue();
});
