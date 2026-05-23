<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Enums;

use Capell\Admin\Contracts\ConfiguratorInterface;
use Capell\Admin\Contracts\ConfiguratorTypeEnumInterface;

enum ConfiguratorTypeEnum: string implements ConfiguratorTypeEnumInterface
{
    case LayoutContainer = 'LayoutContainers';

    case LayoutBlock = 'LayoutBlocks';

    case Block = 'Blocks';

    case BlockAsset = 'BlockAssets';

    /**
     * @return array<string, list<class-string<ConfiguratorInterface>>>
     */
    public static function getAllConfigurators(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $enum): array => [$enum->value => $enum->getConfigurators()])
            ->all();
    }

    public static function fromName(string $name): ?static
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    public function getConfigurators(): array
    {
        return match ($this) {
            self::LayoutContainer => LayoutContainerConfiguratorEnum::cases(),
            self::LayoutBlock => LayoutBlockConfiguratorEnum::cases(),
            self::Block => BlockConfiguratorEnum::cases(),
            self::BlockAsset => BlockAssetConfiguratorEnum::cases(),
        };
    }

    public function getName(): string
    {
        return $this->name;
    }
}
