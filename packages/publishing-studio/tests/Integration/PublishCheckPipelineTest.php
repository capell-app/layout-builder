<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Tests\Integration;

use Capell\PublishingStudio\Checks\PublishCheck;
use Capell\PublishingStudio\Checks\PublishCheckPipeline;
use Capell\PublishingStudio\Checks\PublishCheckResult;
use Capell\PublishingStudio\Checks\PublishCheckSeverity;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Support\Facades\Config;

class FixturePassingCheck implements PublishCheck
{
    public function identifier(): string
    {
        return 'fixture-pass';
    }

    public function label(): string
    {
        return 'Fixture pass';
    }

    public function run(Workspace $workspace): PublishCheckResult
    {
        return new PublishCheckResult(
            identifier: $this->identifier(),
            label: $this->label(),
            severity: PublishCheckSeverity::Info,
        );
    }
}

class FixtureFailingCheck implements PublishCheck
{
    public function identifier(): string
    {
        return 'fixture-fail';
    }

    public function label(): string
    {
        return 'Fixture fail';
    }

    public function run(Workspace $workspace): PublishCheckResult
    {
        return new PublishCheckResult(
            identifier: $this->identifier(),
            label: $this->label(),
            severity: PublishCheckSeverity::Error,
            messages: ['oh no something broke'],
            entityRefs: [['model' => 'Page', 'uuid' => 'abc']],
        );
    }
}

it('runs each configured check and returns their results', function (): void {
    Config::set('capell.publishing-studio.publish_checks', [
        FixturePassingCheck::class,
        FixtureFailingCheck::class,
    ]);

    $workspace = Workspace::factory()->create();

    $results = resolve(PublishCheckPipeline::class)->run($workspace);

    expect($results)->toHaveCount(2)
        ->and($results[0]->identifier)->toBe('fixture-pass')
        ->and($results[1]->identifier)->toBe('fixture-fail');
});

it('detects blocking errors when any result is Error severity with findings', function (): void {
    Config::set('capell.publishing-studio.publish_checks', [
        FixtureFailingCheck::class,
    ]);

    $pipeline = resolve(PublishCheckPipeline::class);
    $results = $pipeline->run(Workspace::factory()->create());

    expect($pipeline->hasBlockingErrors($results))->toBeTrue();
});

it('clean Error-severity results are not blocking', function (): void {
    Config::set('capell.publishing-studio.publish_checks', [
        FixturePassingCheck::class,
    ]);

    $pipeline = resolve(PublishCheckPipeline::class);
    $results = $pipeline->run(Workspace::factory()->create());

    expect($pipeline->hasBlockingErrors($results))->toBeFalse();
});
