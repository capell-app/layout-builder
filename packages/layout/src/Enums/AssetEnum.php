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

    public function getLabel(): string
    {
        return match ($this) {
            self::Content => __('capell-admin::generic.content'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Content => config('capell-layout.assets.content.color', 'info'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Content => config('capell-layout.assets.content.icon'),
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
}
