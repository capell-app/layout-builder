<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use Capell\Layout\Models\Content;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Model;

enum AssetEnum: string implements HasColor, HasIcon, HasLabel
{
    case Content = 'content';

    public function getColor(): string
    {
        return match ($this) {
            self::Content => config('capell-layout.assets.content.color', 'info'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Content => config('capell-layout.assets.content.icon', 'heroicon-o-rectangle-stack'),
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Content => __('capell-admin::generic.content'),
        };
    }

    /**
     * @return class-string<Model>
     */
    public function getModel(): string
    {
        return match ($this) {
            self::Content => config('capell-layout.assets.content.model', Content::class),
        };
    }

    public function getComponent(): string
    {
        return match ($this) {
            self::Content => AssetComponentEnum::Content->value,
        };
    }

    public function hasTranslation(): bool
    {
        return match ($this) {
            self::Content => true,
        };
    }
}
