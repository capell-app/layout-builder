<?php

declare(strict_types=1);

use Capell\EmailStudio\Actions\RegisterEmailTemplateAction;
use Capell\EmailStudio\Models\EmailTemplateRegistration;

it('upserts registered templates by package, key, and site scope', function (): void {
    $registration = RegisterEmailTemplateAction::run(
        key: 'forms.confirmation',
        name: 'Form confirmation',
        variables: ['name', 'submission_reference'],
        description: 'Sent after a form submission.',
        packageName: 'capell-app/form-builder',
        siteId: 12,
        siteScopeKey: 'site:12',
    );

    $updatedRegistration = RegisterEmailTemplateAction::run(
        key: 'forms.confirmation',
        name: 'Updated confirmation',
        variables: ['name'],
        description: 'Updated description.',
        packageName: 'capell-app/form-builder',
        siteId: 12,
        siteScopeKey: 'site:12',
    );

    expect($updatedRegistration->is($registration))->toBeTrue()
        ->and(EmailTemplateRegistration::query()->count())->toBe(1)
        ->and($updatedRegistration->refresh()->name)->toBe('Updated confirmation')
        ->and($updatedRegistration->variables)->toBe(['name']);
});
