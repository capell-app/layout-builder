<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Capell\FormBuilder\Providers\FormBuilderServiceProvider;

it('registers form-builder package metadata', function (): void {
    $package = CapellCore::getPackage(FormBuilderServiceProvider::$packageName);

    expect($package->name)->toBe('capell-app/form-builder')
        ->and($package->serviceProviderClass)->toBe(FormBuilderServiceProvider::class)
        ->and($package->path)->toBe(realpath(__DIR__ . '/../../../'))
        ->and($package->getDescription())->toBe(__('capell-form-builder::package.description'));
});

it('registers form-builder models for Capell model enumeration', function (): void {
    $models = CapellCore::getModels();

    expect($models)->toContain(Form::class)
        ->and($models)->toContain(Submission::class);
});
