<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Enums;

use Capell\Core\Enums\AssetComponentEnum;
use Capell\Core\Enums\AssetEnum;

enum FoundationThemeAssetEnum: string
{
    case Page = AssetEnum::Page->value;

    public function getAsset(): AssetEnum
    {
        return AssetEnum::from($this->value);
    }

    public function getComponent(): string
    {
        return match ($this) {
            self::Page => AssetComponentEnum::Page->value,
        };
    }
}
