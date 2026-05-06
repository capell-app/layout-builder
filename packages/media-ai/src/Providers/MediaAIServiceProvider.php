<?php

declare(strict_types=1);

namespace Capell\MediaAI\Providers;

use Capell\Admin\Contracts\Extenders\MediaEditActionExtender;
use Capell\Core\Enums\PackageTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\MediaAI\Contracts\ImageDoctor;
use Capell\MediaAI\Filament\MediaAIEditActionExtender;
use Capell\MediaAI\Support\NullImageDoctor;
use Composer\InstalledVersions;
use Spatie\LaravelPackageTools\Package;

final class MediaAIServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-media-ai';

    public static string $packageName = 'capell-app/media-ai';

    public static PackageTypeEnum $type = PackageTypeEnum::Package;

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        $this->app->singletonIf(ImageDoctor::class, NullImageDoctor::class);

        CapellCore::registerPackage(
            self::$packageName,
            type: self::getType(),
            serviceProviderClass: self::class,
            path: realpath(__DIR__ . '/../..'),
            version: $this->version(),
            description: fn (): string => 'Optional AI-assisted media actions for Capell.',
        );

        if (config('capell-media-ai.enabled', true)) {
            $this->app->tag(MediaAIEditActionExtender::class, MediaEditActionExtender::TAG);
        }
    }

    private function version(): string
    {
        if (! class_exists(InstalledVersions::class)) {
            return 'dev';
        }

        if (! InstalledVersions::isInstalled(self::$packageName)) {
            return 'dev';
        }

        return InstalledVersions::getPrettyVersion(self::$packageName) ?? 'dev';
    }
}
