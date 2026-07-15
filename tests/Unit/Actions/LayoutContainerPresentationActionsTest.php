<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Actions\ResetLayoutContainerThemeSettingsAction;
use Capell\LayoutBuilder\Actions\ResolveLayoutContainerPresentationAction;
use Capell\LayoutBuilder\Contracts\LayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Tests\Fixtures\Presentation\FailingLayoutContainerThemePresentationProjector;
use Capell\LayoutBuilder\Tests\Fixtures\Presentation\TestLayoutContainerThemePresentationProjector;
use Illuminate\Support\Facades\Log;

it('normalizes portable container presentation and projects only the active theme namespace', function (): void {
    app()->tag([TestLayoutContainerThemePresentationProjector::class], LayoutContainerThemePresentationProjector::TAG);

    $presentation = ResolveLayoutContainerPresentationAction::run([
        'meta' => [
            'spacing' => 'md',
            'padding' => 'sm',
            'padding_tablet' => ['t-md', 'b-lg'],
            'padding_desktop' => ['none'],
            'border' => 'subtle',
            'margin' => ['t-sm'],
            'theme_settings' => [
                'test-theme' => ['tone' => 'muted'],
                'inactive-theme' => ['private' => 'ignored'],
            ],
        ],
    ], 'test-theme', 'hero');

    expect($presentation->spacing)->toBe('md')
        ->and($presentation->padding->base)->toBe(['sm'])
        ->and($presentation->padding->tablet)->toBe(['t-md', 'b-lg'])
        ->and($presentation->padding->desktop)->toBe(['none'])
        ->and($presentation->border)->toBe('subtle')
        ->and($presentation->margin)->toBe(['t-sm'])
        ->and($presentation->theme?->toArray())->toBe(['tone' => 'muted'])
        ->and($presentation->classes())->toContain(
            'capell-container-spacing-md',
            'capell-container-padding-sm',
            'capell-container-padding-tablet-t-md',
            'capell-container-padding-desktop-none',
            'capell-container-border-subtle',
            'capell-container-margin-t-sm',
            'test-container-tone-muted',
        );
});

it('uses safe defaults for invalid portable values and a missing projector', function (): void {
    $presentation = ResolveLayoutContainerPresentationAction::run([
        'meta' => [
            'spacing' => 'custom',
            'padding' => ['sm', 'unsafe'],
            'padding_tablet' => [],
            'border' => 'rainbow',
            'margin' => false,
        ],
    ], 'unregistered-theme', 'main');

    expect($presentation->spacing)->toBeNull()
        ->and($presentation->padding->base)->toBe(['sm'])
        ->and($presentation->padding->tablet)->toBeNull()
        ->and($presentation->border)->toBeNull()
        ->and($presentation->margin)->toBe([])
        ->and($presentation->theme)->toBeNull();
});

it('normalizes conflicting persisted padding before producing public classes', function (): void {
    $presentation = ResolveLayoutContainerPresentationAction::run([
        'meta' => [
            'padding' => ['none', 'sm'],
            'padding_tablet' => ['sm', 'md', 't-lg'],
            'padding_desktop' => ['t-sm', 't-lg', 'b-md'],
        ],
    ]);

    expect($presentation->padding->base)->toBe(['none'])
        ->and($presentation->padding->tablet)->toBe(['md'])
        ->and($presentation->padding->desktop)->toBe(['t-lg', 'b-md']);
});

it('isolates projector failures from public rendering without logging raw state', function (): void {
    app()->tag([FailingLayoutContainerThemePresentationProjector::class], LayoutContainerThemePresentationProjector::TAG);
    Log::spy();

    $presentation = ResolveLayoutContainerPresentationAction::run([
        'meta' => [
            'theme_settings' => [
                'failing-theme' => ['signed_url' => 'https://admin.test/private'],
            ],
        ],
    ], 'failing-theme', 'main');

    expect($presentation->theme)->toBeNull();

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Layout container theme presentation projection failed.'
                && $context['theme_key'] === 'failing-theme'
                && $context['container_key'] === 'main'
                && ! array_key_exists('state', $context)
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'signed_url'));
});

it('resets only the requested theme namespace in memory', function (): void {
    $meta = [
        'spacing' => 'lg',
        'theme_settings' => [
            'active' => ['tone' => 'muted'],
            'inactive' => ['tone' => 'contrast'],
        ],
    ];

    $reset = ResetLayoutContainerThemeSettingsAction::run($meta, 'active');

    expect($reset)->toBe([
        'spacing' => 'lg',
        'theme_settings' => [
            'inactive' => ['tone' => 'contrast'],
        ],
    ])->and($meta['theme_settings'])->toHaveKey('active');
});

it('removes the empty theme settings parent after the final namespace is reset', function (): void {
    $reset = ResetLayoutContainerThemeSettingsAction::run([
        'border' => 'none',
        'theme_settings' => ['active' => ['tone' => 'muted']],
    ], 'active');

    expect($reset)->toBe(['border' => 'none']);
});
