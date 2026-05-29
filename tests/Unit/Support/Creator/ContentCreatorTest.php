<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Support\Creator\ContentCreator;

it('falls back to english demo content when a requested translation is missing', function (): void {
    $creator = (new ReflectionClass(ContentCreator::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(ContentCreator::class, 'translationDataFor');

    $translationData = $method->invoke($creator, [
        'en' => [
            'title' => 'English title',
            'content' => '<p>English content</p>',
        ],
        'fr' => [
            'title' => 'French title',
            'content' => '<p>French content</p>',
        ],
    ], 'de');

    expect($translationData)->toBe([
        'title' => 'English title',
        'content' => '<p>English content</p>',
    ]);
});
