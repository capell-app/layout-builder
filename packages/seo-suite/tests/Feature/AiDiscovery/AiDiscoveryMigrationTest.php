<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\SeoSuite\Console\Commands\InstallCommand;
use Capell\SeoSuite\Settings\SeoSuiteSettings;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;

$composerAutoloader = require getcwd() . '/vendor/autoload.php';

if ($composerAutoloader instanceof ClassLoader) {
    $packageRoot = dirname(__DIR__, 3);

    $composerAutoloader->addPsr4('Capell\\SeoSuite\\', $packageRoot . '/src');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Database\\Factories\\', $packageRoot . '/database/factories');
    $composerAutoloader->addPsr4('Capell\\SeoSuite\\Tests\\', $packageRoot . '/tests');
}

function createAiDiscoveryMigrationLanguage(): Language
{
    return Language::query()->create([
        'name' => 'English',
        'locale' => 'en',
        'code' => 'en',
        'flag' => 'gb-eng',
        'status' => true,
        'default' => true,
        'order' => 1,
    ]);
}

function runAiDiscoverySettingsMigrations(array $migrations, string $basePath): void
{
    $settingsMigrator = resolve(SettingsMigrator::class);

    foreach ($migrations as $migrationFile) {
        $path = sprintf('%s/%s.php', $basePath, $migrationFile);
        /** @var SettingsMigration $migration */
        $migration = require $path;

        if (method_exists($migration, 'setMigrationAssistant')) {
            $migration->setMigrationAssistant($settingsMigrator);
        }

        $migration->up();
    }
}

it('creates ai discovery tables with expected columns and indexes', function (): void {
    expect(Schema::hasTable('ai_discovery_site_profiles'))->toBeTrue()
        ->and(Schema::hasColumns('ai_discovery_site_profiles', [
            'id',
            'site_id',
            'language_id',
            'llms_txt_enabled',
            'llms_full_txt_enabled',
            'markdown_pages_enabled',
            'accept_markdown_enabled',
            'default_include_pages',
            'max_full_txt_pages',
            'max_full_txt_bytes',
            'cache_ttl_seconds',
            'default_section',
            'intro_markdown',
            'status',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_discovery_page_profiles', [
            'id',
            'page_id',
            'site_id',
            'language_id',
            'include_in_ai_index',
            'exclude_reason',
            'summary',
            'section',
            'priority',
            'markdown_override',
            'generated_markdown',
            'markdown_hash',
            'last_generated_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_discovery_crawler_rules', [
            'id',
            'site_id',
            'provider',
            'user_agent',
            'purpose',
            'directive',
            'path',
            'crawl_delay_seconds',
            'enabled',
            'source_url',
            'notes',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_discovery_snapshots', [
            'id',
            'site_id',
            'site_domain_id',
            'language_id',
            'kind',
            'page_id',
            'context_key',
            'content_hash',
            'byte_size',
            'cache_key',
            'generated_at',
            'expires_at',
            'status',
            'error_message',
            'created_at',
            'updated_at',
        ]))->toBeTrue();
});

it('creates ai discovery records with expected database defaults', function (): void {
    $language = createAiDiscoveryMigrationLanguage();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $page = Page::factory()->site($site)->withTranslations($language)->create();

    DB::table('ai_discovery_site_profiles')->insert([
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('ai_discovery_page_profiles')->insert([
        'page_id' => $page->getKey(),
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('ai_discovery_crawler_rules')->insert([
        'provider' => 'OpenAI',
        'user_agent' => 'GPTBot',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('ai_discovery_snapshots')->insert([
        'site_id' => $site->getKey(),
        'language_id' => $language->getKey(),
        'kind' => 'llms_txt',
        'context_key' => 'default:site',
        'content_hash' => hash('sha256', 'content'),
        'cache_key' => 'capell-seo-suite:ai-discovery:test',
        'generated_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $siteProfile = DB::table('ai_discovery_site_profiles')->first();
    $pageProfile = DB::table('ai_discovery_page_profiles')->first();
    $crawlerRule = DB::table('ai_discovery_crawler_rules')->first();
    $snapshot = DB::table('ai_discovery_snapshots')->first();

    expect((bool) $siteProfile->llms_txt_enabled)->toBeTrue()
        ->and((bool) $siteProfile->llms_full_txt_enabled)->toBeFalse()
        ->and((bool) $siteProfile->markdown_pages_enabled)->toBeTrue()
        ->and((bool) $siteProfile->accept_markdown_enabled)->toBeFalse()
        ->and((bool) $siteProfile->default_include_pages)->toBeTrue()
        ->and((int) $siteProfile->max_full_txt_pages)->toBe(50)
        ->and((int) $siteProfile->max_full_txt_bytes)->toBe(250000)
        ->and((int) $siteProfile->cache_ttl_seconds)->toBe(3600)
        ->and($siteProfile->default_section)->toBe('Pages')
        ->and($siteProfile->status)->toBe('enabled')
        ->and((bool) $pageProfile->include_in_ai_index)->toBeTrue()
        ->and($pageProfile->section)->toBe('Pages')
        ->and((int) $pageProfile->priority)->toBe(500)
        ->and($crawlerRule->purpose)->toBe('unknown')
        ->and($crawlerRule->directive)->toBe('disallow')
        ->and($crawlerRule->path)->toBe('/')
        ->and((bool) $crawlerRule->enabled)->toBeTrue()
        ->and((int) $snapshot->byte_size)->toBe(0)
        ->and($snapshot->status)->toBe('fresh');
});

it('creates expected ai discovery indexes on sqlite', function (): void {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        expect(true)->toBeTrue();

        return;
    }

    expect(aiDiscoveryMigrationIndexColumns('ai_discovery_site_profiles', 'ai_discovery_site_profile_unique_context'))->toBe(['site_id', 'language_id'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_page_profiles', 'ai_discovery_page_profile_unique_context'))->toBe(['page_id', 'site_id', 'language_id'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_page_profiles', 'ai_discovery_page_profile_lookup'))->toBe(['site_id', 'language_id', 'include_in_ai_index'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_page_profiles', 'ai_discovery_page_profile_ordering'))->toBe(['site_id', 'language_id', 'section', 'priority'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_crawler_rules', 'ai_discovery_crawler_rule_lookup'))->toBe(['site_id', 'enabled', 'purpose'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_crawler_rules', 'ai_discovery_crawler_rule_provider_user_agent'))->toBe(['provider', 'user_agent'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_snapshots', 'ai_discovery_snapshot_unique_context'))->toBe(['site_id', 'language_id', 'kind', 'context_key'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_snapshots', 'ai_discovery_snapshot_lookup'))->toBe(['site_id', 'language_id', 'kind', 'status'])
        ->and(aiDiscoveryMigrationIndexColumns('ai_discovery_snapshots', 'ai_discovery_snapshot_expiry'))->toBe(['expires_at']);
});

it('adds ai discovery seo suite settings defaults', function (): void {
    runAiDiscoverySettingsMigrations(
        [
            '2026_05_10_190871_03_create_seo_suite_settings',
            '2026_05_10_190871_04_update_seo_suite_settings_add_ai_discovery',
        ],
        dirname(__DIR__, 3) . '/database/settings',
    );

    $settings = resolve(SeoSuiteSettings::class);

    expect($settings->ai_discovery_audit_enabled)->toBeTrue()
        ->and($settings->ai_discovery_default_enabled)->toBeTrue()
        ->and($settings->ai_discovery_crawler_policy)->toBe('search_visible_training_restricted');
});

it('publishes the ai discovery settings migration during seo suite install', function (): void {
    $command = new ReflectionClass(InstallCommand::class);
    $source = file_get_contents((string) $command->getFileName());

    expect($source)->toContain('create_ai_discovery_site_profiles_table')
        ->and($source)->toContain('create_ai_discovery_page_profiles_table')
        ->and($source)->toContain('create_ai_discovery_crawler_rules_table')
        ->and($source)->toContain('create_ai_discovery_snapshots_table')
        ->and($source)->toContain('2026_05_10_190871_04_update_seo_suite_settings_add_ai_discovery');
});

/**
 * @return list<string>
 */
function aiDiscoveryMigrationIndexColumns(string $table, string $index): array
{
    $indexes = collect(DB::select(sprintf("PRAGMA index_list('%s')", $table)));
    $matchingIndex = $indexes->first(fn (object $row): bool => $row->name === $index);

    expect($matchingIndex)->not->toBeNull();

    return collect(DB::select(sprintf("PRAGMA index_info('%s')", $index)))
        ->pluck('name')
        ->values()
        ->all();
}
