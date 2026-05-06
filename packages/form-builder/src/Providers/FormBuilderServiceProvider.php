<?php

declare(strict_types=1);

namespace Capell\FormBuilder\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Actions\RegisterBlazeOptimizedViewsAction;
use Capell\Core\Data\VendorAssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\FormBuilder\Enums\LivewireComponentEnum;
use Capell\FormBuilder\Enums\ResourceEnum;
use Capell\FormBuilder\Models\Form;
use Capell\FormBuilder\Models\Submission;
use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;

class FormBuilderServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-form-builder';

    public static string $packageName = 'capell-app/form-builder';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasViews(self::$name)
            ->hasTranslations()
            ->hasMigrations([
                'create_form-builder_table',
                'create_submissions_table',
            ]);
    }

    public function registeringPackage(): void
    {
        $this->registerPackageMetadata();

        $this->app->booted(function (): void {
            if (! $this->isPackageInstalled()) {
                return;
            }

            $this->bootInstalledPackage();
        });
    }

    public function packageBooted(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        Relation::morphMap([
            'form' => Form::class,
            'form_submission' => Submission::class,
        ], merge: true);
    }

    private function bootInstalledPackage(): self
    {
        return $this
            ->registerModels()
            ->registerPackageAssets()
            ->registerBlazeComponents()
            ->registerResources()
            ->registerLivewireComponents()
            ->registerBladeComponents();
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::getPackage(static::$packageName)->isInstalled();
    }

    private function registerPackageMetadata(): self
    {
        CapellCore::registerPackage(
            static::$packageName,
            type: static::getType(),
            serviceProviderClass: static::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->getVersion(),
            description: fn (): string => __('capell-form-builder::package.description'),
        );

        return $this;
    }

    private function registerModels(): self
    {
        CapellCore::registerModels([
            Form::class,
            Submission::class,
        ]);

        return $this;
    }

    private function registerPackageAssets(): self
    {
        CapellCore::registerVendorAsset(
            VendorAssetData::tailwindSource('resources/views/**/*.blade.php', static::$packageName),
        );

        return $this;
    }

    private function registerBlazeComponents(): self
    {
        RegisterBlazeOptimizedViewsAction::run(__DIR__ . '/../../resources/views/components');

        return $this;
    }

    private function registerResources(): self
    {
        foreach (ResourceEnum::cases() as $resource) {
            if (! class_exists($resource->value)) {
                continue;
            }

            CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
                class: $resource->value,
                group: $resource->name,
            ));
        }

        return $this;
    }

    private function registerLivewireComponents(): self
    {
        if ($this->isLivewireV3()) {
            foreach (LivewireComponentEnum::getComponents() as $name => $component) {
                if (! $component) {
                    continue;
                }

                if (! class_exists($component)) {
                    continue;
                }

                Livewire::component($name, $component);
            }
        } else {
            Livewire::addNamespace(
                namespace: 'capell-form-builder',
                classNamespace: 'Capell\\FormBuilder\\Livewire',
                classPath: __DIR__ . '/../Livewire',
                classViewPath: __DIR__ . '/../../resources/views/livewire',
            );
        }

        return $this;
    }

    private function registerBladeComponents(): self
    {
        Blade::componentNamespace('Capell\\FormBuilder\\View\\Components', 'capell-form-builder');
        Blade::anonymousComponentNamespace('Capell\\FormBuilder\\View\\Components');

        return $this;
    }

    private function getVersion(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(static::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(static::$packageName) ?? 'dev';
    }

    private function isLivewireV3(): bool
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled('livewire/livewire')) {
            return true;
        }

        $version = InstalledVersions::getVersion('livewire/livewire');

        return version_compare($version, '4.0.0', '<');
    }
}
