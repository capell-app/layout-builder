<?php

declare(strict_types=1);

namespace Capell\Plugins\Data;

use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class PluginManifestData extends Data
{
    /**
     * @param  list<string>  $capabilities
     * @param  list<string>|null  $categories
     * @param  list<string>|null  $tags
     * @param  list<string>|null  $requires
     * @param  list<string>|null  $screenshots
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly string $slug,
        public readonly string $version,
        public readonly string $description,
        public readonly string $vendor,
        public readonly LicenseModel $licenseModel,
        public readonly PluginCompatibilityData $compatibility,
        public readonly array $capabilities,
        public readonly PluginKind $kind,
        public readonly string $entrypoint,
        public readonly ?array $categories = null,
        public readonly ?array $tags = null,
        public readonly ?array $requires = null,
        public readonly ?array $screenshots = null,
        public readonly ?string $longDescriptionFile = null,
        public readonly ?PluginPriceData $price = null,
        public readonly ?int $trialDays = null,
        public readonly ?string $icon = null,
        public readonly ?PluginSupportData $support = null,
        public readonly ?PluginHooksData $hooks = null,
    ) {}
}
