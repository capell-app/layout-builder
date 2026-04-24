<?php

declare(strict_types=1);

use Capell\Plugins\Enums\LicenseStatus;
use Capell\Plugins\Jobs\ValidateLicensesJob;
use Capell\Plugins\Models\MarketplacePlugin;
use Capell\Plugins\Models\MarketplacePluginLicense;
use Capell\Plugins\Services\AnystackClient;
use Capell\Plugins\Tests\Fixtures\Jobs\RecordingValidateLicenseAction;
use Illuminate\Support\Facades\Http;

function makeJobRecordingAction(array $throwsById = []): RecordingValidateLicenseAction
{
    $client = new AnystackClient('https://api.anystack.sh', null, 5);

    return new RecordingValidateLicenseAction($client, $throwsById);
}

test('job only processes usable statuses', function (): void {
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

    $recorder = makeJobRecordingAction();
    (new ValidateLicensesJob)->handle($recorder);

    $seenIds = $recorder->getSeenIds();
    expect($seenIds)->toEqualCanonicalizing(
        [$active->id, $trial->id, $pastDue->id],
    );
});

test('throwing action does not stop iteration', function (): void {
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

    $action = makeJobRecordingAction([$first->id => new RuntimeException('boom')]);

    try {
        (new ValidateLicensesJob)->handle($action);
    } catch (Throwable $throwable) {
        expect()->fail('Job should swallow per-license errors: ' . $throwable->getMessage());
    }

    $seenIds = $action->getSeenIds();
    expect($seenIds)->toContain($first->id);
    expect($seenIds)->toContain($second->id);
});
