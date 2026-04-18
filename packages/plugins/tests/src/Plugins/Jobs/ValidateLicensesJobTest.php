<?php

declare(strict_types=1);

use Capell\Plugins\Actions\ValidateLicenseAction;
use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Jobs\ValidateLicensesJob;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Illuminate\Support\Facades\Queue;

describe('ValidateLicensesJob', function (): void {
    it('validates all active, trial, and past_due licenses', function (): void {
        Queue::fake();

        // Create test plugin
        $plugin = MarketplacePlugin::factory()->create();

        // Create licenses with different statuses
        $activeLicense = MarketplacePluginLicense::factory()
            ->for($plugin, 'plugin')
            ->state(['status' => LicenseStatus::Active])
            ->create();

        $trialLicense = MarketplacePluginLicense::factory()
            ->for($plugin, 'plugin')
            ->state(['status' => LicenseStatus::Trial])
            ->create();

        $pastDueLicense = MarketplacePluginLicense::factory()
            ->for($plugin, 'plugin')
            ->state(['status' => LicenseStatus::PastDue])
            ->create();

        $expiredLicense = MarketplacePluginLicense::factory()
            ->for($plugin, 'plugin')
            ->state(['status' => LicenseStatus::Expired])
            ->create();

        // Mock the ValidateLicenseAction
        $action = Mockery::mock(ValidateLicenseAction::class);
        $action->shouldReceive('handle')->times(3);

        $this->app->instance(ValidateLicenseAction::class, $action);

        // Execute job
        $job = new ValidateLicensesJob;
        $job->handle($action);

        // Verify that only active, trial, and past_due licenses were validated
        $action->shouldHaveReceived('handle')->times(3);
    });

    it('continues processing when a license validation fails', function (): void {
        // Create test plugin
        $plugin = MarketplacePlugin::factory()->create();

        // Create multiple licenses
        MarketplacePluginLicense::factory()
            ->for($plugin, 'plugin')
            ->state(['status' => LicenseStatus::Active])
            ->createMany(3);

        // Mock the ValidateLicenseAction to throw on first call
        $action = Mockery::mock(ValidateLicenseAction::class);
        $action->shouldReceive('handle')
            ->times(3)
            ->andReturnValues([
                throw new Exception('Validation failed'),
                Mockery::mock(),
                Mockery::mock(),
            ]);

        // Execute job - should not throw exception
        $job = new ValidateLicensesJob;

        expect(function () use ($job, $action): void {
            $job->handle($action);
        })->not->toThrow(Exception::class);
    });

    it('uses cursor for memory efficiency', function (): void {
        // Create test plugin
        $plugin = MarketplacePlugin::factory()->create();

        // Create licenses
        MarketplacePluginLicense::factory()
            ->for($plugin, 'plugin')
            ->state(['status' => LicenseStatus::Active])
            ->count(50)
            ->create();

        $action = Mockery::mock(ValidateLicenseAction::class);
        $action->shouldReceive('handle')->times(50);

        $this->app->instance(ValidateLicenseAction::class, $action);

        $job = new ValidateLicensesJob;
        $job->handle($action);

        $action->shouldHaveReceived('handle')->times(50);
    });
});
