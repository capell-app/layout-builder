<?php

declare(strict_types=1);

namespace Capell\Tests\Plugins\Jobs;

use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Jobs\ValidateLicensesJob;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Capell\Tests\Plugins\PluginsTestCase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class ValidateLicensesJobTest extends PluginsTestCase
{
    public function test_job_only_processes_usable_statuses(): void
    {
        Http::fake();

        $plugin = MarketplacePlugin::factory()->create([
            'anystack_product_id' => 'prod_xyz',
        ]);

        $active = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_a',
            'status' => LicenseStatus::Active,
        ]);
        $trial = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_b',
            'status' => LicenseStatus::Trial,
        ]);
        $pastDue = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_c',
            'status' => LicenseStatus::PastDue,
        ]);
        MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_d',
            'status' => LicenseStatus::Expired,
        ]);
        MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_e',
            'status' => LicenseStatus::Revoked,
        ]);

        $recorder = $this->makeRecordingAction();
        (new ValidateLicensesJob)->handle($recorder);

        $seenIds = $recorder->getSeenIds();
        $this->assertEqualsCanonicalizing(
            [$active->id, $trial->id, $pastDue->id],
            $seenIds,
        );
    }

    public function test_throwing_action_does_not_stop_iteration(): void
    {
        Http::fake();

        $plugin = MarketplacePlugin::factory()->create([
            'anystack_product_id' => 'prod_xyz',
        ]);

        $first = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_1',
            'status' => LicenseStatus::Active,
        ]);
        $second = MarketplacePluginLicense::factory()->create([
            'marketplace_plugin_id' => $plugin->id,
            'site_id' => 'site_2',
            'status' => LicenseStatus::Active,
        ]);

        $action = $this->makeRecordingAction([$first->id => new RuntimeException('boom')]);

        try {
            (new ValidateLicensesJob)->handle($action);
        } catch (Throwable $throwable) {
            $this->fail('Job should swallow per-license errors: ' . $throwable->getMessage());
        }

        $seenIds = $action->getSeenIds();
        $this->assertContains($first->id, $seenIds, 'first license was attempted');
        $this->assertContains($second->id, $seenIds, 'iteration continued after throw');
    }

    /**
     * @param  array<int, Throwable>  $throwsById
     */
    private function makeRecordingAction(array $throwsById = []): RecordingValidateLicenseAction
    {
        $client = new AnystackClient('https://api.anystack.sh', null, 5);

        return new RecordingValidateLicenseAction($client, $throwsById);
    }
}
