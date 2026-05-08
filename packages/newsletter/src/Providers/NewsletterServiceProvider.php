<?php

declare(strict_types=1);

namespace Capell\Newsletter\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\Newsletter\Enums\ProviderType;
use Capell\Newsletter\Filament\Settings\NewsletterSettingsSchema;
use Capell\Newsletter\Listeners\SubscribeFromFormSubmission;
use Capell\Newsletter\Models\ConsentEvent;
use Capell\Newsletter\Models\FormMapping;
use Capell\Newsletter\Models\ImportBatch;
use Capell\Newsletter\Models\ProviderAudience;
use Capell\Newsletter\Models\ProviderConnection;
use Capell\Newsletter\Models\ProviderInterestMapping;
use Capell\Newsletter\Models\ProviderSubscriber;
use Capell\Newsletter\Models\PublicToken;
use Capell\Newsletter\Models\Segment;
use Capell\Newsletter\Models\Subscriber;
use Capell\Newsletter\Models\SyncAttempt;
use Capell\Newsletter\Settings\NewsletterSettings;
use Capell\Newsletter\Support\NewsletterAudienceRegistry;
use Capell\Newsletter\Support\Providers\CampaignMonitorProviderAdapter;
use Capell\Newsletter\Support\Providers\FakeProviderAdapter;
use Capell\Newsletter\Support\Providers\KitProviderAdapter;
use Capell\Newsletter\Support\Providers\MailchimpProviderAdapter;
use Capell\Newsletter\Support\Providers\ProviderAdapterRegistry;
use Capell\Newsletter\Support\SegmentAudienceProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;

class NewsletterServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-newsletter';

    public static string $packageName = 'capell-app/newsletter';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-newsletter')
            ->hasTranslations()
            ->hasRoute('web')
            ->hasMigrations([
                'create_newsletter_subscribers_table',
                'create_newsletter_provider_connections_table',
                'create_newsletter_consent_events_table',
                'create_newsletter_public_tokens_table',
                'create_newsletter_form_mappings_table',
                'create_newsletter_provider_audiences_table',
                'create_newsletter_provider_interest_mappings_table',
                'create_newsletter_provider_subscribers_table',
                'create_newsletter_sync_attempts_table',
                'create_newsletter_segments_table',
                'create_newsletter_import_batches_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata();
        $this->registerAdapterRegistry();
        $this->registerSettingsSchemas();
        $this->app->singleton(NewsletterAudienceRegistry::class);

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerModels()
                ->registerProtectedTables()
                ->registerAudienceProviders()
                ->registerListeners();
        });
    }

    public function packageBooted(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        Relation::morphMap([
            'newsletter_subscriber' => Subscriber::class,
            'newsletter_segment' => Segment::class,
        ], merge: true);
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: CapellCore::getInstalledPrettyVersion(self::$packageName),
            description: fn (): string => __('capell-newsletter::package.description'),
        );

        return $this;
    }

    private function registerAdapterRegistry(): self
    {
        $this->app->bind(ProviderAdapterRegistry::class, function (Container $container): ProviderAdapterRegistry {
            $registry = new ProviderAdapterRegistry($container);
            $registry->register(ProviderType::Mailchimp, MailchimpProviderAdapter::class);
            $registry->register(ProviderType::Kit, KitProviderAdapter::class);
            $registry->register(ProviderType::CampaignMonitor, CampaignMonitorProviderAdapter::class);
            $registry->register(ProviderType::Fake, FakeProviderAdapter::class);

            return $registry;
        });

        return $this;
    }

    private function registerSettingsSchemas(): self
    {
        $registry = resolve(SettingsSchemaRegistry::class);
        $registry->registerSettingsClass('newsletter', NewsletterSettings::class);
        $registry->register('newsletter', NewsletterSettingsSchema::class);

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            Subscriber::class,
            ConsentEvent::class,
            PublicToken::class,
            FormMapping::class,
            ProviderConnection::class,
            ProviderAudience::class,
            ProviderInterestMapping::class,
            ProviderSubscriber::class,
            SyncAttempt::class,
            Segment::class,
            ImportBatch::class,
        ]);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        $tables = config('capell-newsletter.tables', []);

        if (! is_array($tables)) {
            return $this;
        }

        foreach ($tables as $tableName) {
            if (! is_string($tableName)) {
                continue;
            }

            if ($tableName === '') {
                continue;
            }

            CapellCore::registerProtectedTable(static fn (): string => $tableName);
        }

        return $this;
    }

    private function registerAudienceProviders(): self
    {
        $this->app->make(NewsletterAudienceRegistry::class)
            ->register($this->app->make(SegmentAudienceProvider::class));

        return $this;
    }

    private function registerListeners(): self
    {
        $formSubmittedEvent = implode('\\', ['Capell', 'FormBuilder', 'Events', 'FormSubmitted']);

        if (class_exists($formSubmittedEvent)) {
            Event::listen($formSubmittedEvent, SubscribeFromFormSubmission::class);
        }

        return $this;
    }
}
