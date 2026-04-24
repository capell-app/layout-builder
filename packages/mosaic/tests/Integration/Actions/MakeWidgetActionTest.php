<?php

declare(strict_types=1);

namespace Capell\Mosaic\Tests\Integration\Actions;

use Capell\Mosaic\Actions\MakeWidgetAction;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->tempViewsDirectory = sys_get_temp_dir() . '/capell-test-widgets-' . uniqid('', true);
});

afterEach(function (): void {
    if (is_dir($this->tempViewsDirectory)) {
        File::deleteDirectory($this->tempViewsDirectory);
    }
});

it('scaffolds a blade view for the widget', function (): void {
    $result = MakeWidgetAction::run('HeroBanner', $this->tempViewsDirectory);

    expect($result->created)->toBeTrue();
    expect($result->viewPath)->toBe($this->tempViewsDirectory . '/hero-banner.blade.php');
    expect(file_exists($result->viewPath))->toBeTrue();
    expect(file_get_contents($result->viewPath))
        ->toContain('HeroBanner widget')
        ->toContain('widget--hero-banner');
});

it('does not overwrite an existing view', function (): void {
    $first = MakeWidgetAction::run('HeroBanner', $this->tempViewsDirectory);
    file_put_contents($first->viewPath, 'custom content');

    $second = MakeWidgetAction::run('HeroBanner', $this->tempViewsDirectory);

    expect($second->created)->toBeFalse();
    expect($second->viewPath)->toBe($first->viewPath);
    expect(file_get_contents($second->viewPath))->toBe('custom content');
});

it('builds a seeder snippet with matching keys and component path', function (): void {
    $result = MakeWidgetAction::run('HeroBanner', $this->tempViewsDirectory);

    expect($result->seederSnippet)
        ->toContain("'key' => 'hero-banner'")
        ->toContain("'name' => 'Hero Banner'")
        ->toContain("'component' => 'widgets.hero-banner'")
        ->toContain('use Capell\Core\Models\Type;')
        ->toContain('use Capell\Mosaic\Models\Widget;');
});

it('normalises the widget name to StudlyCase and kebab-case', function (): void {
    $result = MakeWidgetAction::run('hero_banner', $this->tempViewsDirectory);

    expect($result->viewPath)->toBe($this->tempViewsDirectory . '/hero-banner.blade.php');
    expect($result->seederSnippet)->toContain("'name' => 'Hero Banner'");
});
