<?php

declare(strict_types=1);

use Capell\SeoTools\Support\AiFeatureRegistry;

it('registers and resolves features', function (): void {
    $registry = new AiFeatureRegistry;

    $registry->register('suggest_titles', ['model' => 'gpt-4']);
    $registry->register('suggest_meta', ['model' => 'gpt-4']);

    expect($registry->all())->toHaveKey('suggest_titles')
        ->and($registry->get('suggest_titles'))
        ->toMatchArray(['model' => 'gpt-4']);
});

it('prevents duplicate registration', function (): void {
    $registry = new AiFeatureRegistry;
    $registry->register('suggest_titles', ['model' => 'gpt-4']);

    // Duplicate registration should not throw and should overwrite.
    $registry->register('suggest_titles', ['model' => 'gpt-4']);

    expect($registry->all())
        ->toHaveKey('suggest_titles');
});
