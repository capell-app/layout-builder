<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Forms\Models\Form;
use Capell\Forms\Models\Submission;
use Capell\Forms\Providers\FormsServiceProvider;

it('registers forms package metadata', function (): void {
    $package = CapellCore::getPackage(FormsServiceProvider::$packageName);

    expect($package->name)->toBe('capell-app/forms')
        ->and($package->serviceProviderClass)->toBe(FormsServiceProvider::class)
        ->and($package->path)->toBe(realpath(__DIR__ . '/../../../'))
        ->and($package->getDescription())->toBe(__('capell-forms::package.description'));
});

it('registers forms models for Capell model enumeration', function (): void {
    $models = CapellCore::getModels();

    expect($models)->toContain(Form::class)
        ->and($models)->toContain(Submission::class);
});
