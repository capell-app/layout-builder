<?php

declare(strict_types=1);

namespace Capell\EmailStudio\Providers;

use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\EmailStudio\Enums\EmailProviderType;
use Capell\EmailStudio\Models\EmailEvent;
use Capell\EmailStudio\Models\EmailMessage;
use Capell\EmailStudio\Models\EmailProfile;
use Capell\EmailStudio\Models\EmailRecipient;
use Capell\EmailStudio\Models\EmailReply;
use Capell\EmailStudio\Models\EmailSuppression;
use Capell\EmailStudio\Models\EmailTemplate;
use Capell\EmailStudio\Models\EmailTemplateRegistration;
use Capell\EmailStudio\Models\EmailTemplateVariant;
use Capell\EmailStudio\Models\EmailTrackingToken;
use Capell\EmailStudio\Support\EmailProviderRegistry;
use Capell\EmailStudio\Support\EmailTemplateRegistry;
use Capell\EmailStudio\Support\Providers\FakeEmailProviderAdapter;
use Capell\EmailStudio\Support\Providers\PostmarkEmailProviderAdapter;
use Capell\EmailStudio\Support\Providers\SmtpEmailProviderAdapter;
use Spatie\LaravelPackageTools\Package;

class EmailStudioServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-email-studio';

    public static string $packageName = 'capell-app/email-studio';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile('capell-email-studio')
            ->hasTranslations()
            ->hasRoute('web')
            ->hasMigrations([
                '01_create_email_profiles_table',
                '02_create_email_templates_table',
                '03_create_email_template_variants_table',
                '04_create_email_messages_table',
                '05_create_email_recipients_table',
                '06_create_email_events_table',
                '07_create_email_replies_table',
                '08_create_email_suppressions_table',
                '09_create_email_template_registrations_table',
                '10_create_email_tracking_tokens_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->app->register(AdminServiceProvider::class);
        $this->app->register(FrontendServiceProvider::class);
    }

    public function packageRegistered(): void
    {
        $this->registerPackageMetadata();
        $this->app->singleton(EmailTemplateRegistry::class);
        $this->app->singleton(EmailProviderRegistry::class, static fn (): EmailProviderRegistry => (new EmailProviderRegistry)
            ->register(EmailProviderType::Fake, new FakeEmailProviderAdapter)
            ->register(EmailProviderType::Smtp, new SmtpEmailProviderAdapter)
            ->register(EmailProviderType::Postmark, new PostmarkEmailProviderAdapter));

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this
                ->registerModels()
                ->registerProtectedTables();
        });
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
            description: fn (): string => __('capell-email-studio::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            EmailProfile::class,
            EmailTemplate::class,
            EmailTemplateVariant::class,
            EmailMessage::class,
            EmailRecipient::class,
            EmailEvent::class,
            EmailReply::class,
            EmailSuppression::class,
            EmailTemplateRegistration::class,
            EmailTrackingToken::class,
        ]);

        return $this;
    }

    private function registerProtectedTables(): self
    {
        $tables = config('capell-email-studio.tables', []);

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
}
