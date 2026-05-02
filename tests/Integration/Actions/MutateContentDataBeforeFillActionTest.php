<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\Mosaic\Actions\InstallPackageAction;
use Capell\Mosaic\Actions\MutateContentDataBeforeFillAction;

it('sets type_id to the default section content type', function (): void {
    InstallPackageAction::run();

    $result = MutateContentDataBeforeFillAction::run();

    expect($result)->toHaveKey('type_id')
        ->and($result['type_id'])->toBeInt();
});

it('scaffolds a translation entry per site language keyed by uuid', function (): void {
    InstallPackageAction::run();
    $site = Site::factory()->withTranslations()->create(['default' => true]);

    $result = MutateContentDataBeforeFillAction::run();

    expect($result['translations'])->toBeArray()
        ->and($result['translations'])->toHaveCount($site->translations->count());

    foreach ($result['translations'] as $uuid => $translation) {
        expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i')
            ->and($translation)->toHaveKey('language_id');
    }
});

it('returns null for translations when no default site exists', function (): void {
    InstallPackageAction::run();

    $result = MutateContentDataBeforeFillAction::run();

    expect($result['translations'])->toBeNull();
});

it('throws when no section content type exists', function (): void {
    MutateContentDataBeforeFillAction::run();
})->throws(Exception::class, 'No default content type found');
