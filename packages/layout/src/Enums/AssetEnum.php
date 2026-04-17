<?php

declare(strict_types=1);

namespace Capell\Layout\Enums;

use BackedEnum;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Contracts\Actionable;
use Capell\Layout\Actions\CreateContentAction;
use Capell\Layout\Actions\MutateContentDataBeforeFillAction;
use Capell\Layout\Filament\Resources\Collections\Schemas\CollectionForm;
use Capell\Layout\Models\Collection;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
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

    public function getIcon(): string|BackedEnum
    {
        return match ($this) {
            self::Content => config('capell-layout.assets.content.icon', Heroicon::OutlinedFolder),
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
            self::Content => config('capell-layout.assets.content.model', Collection::class),
        };
    }

    public function getComponent(): string
    {
        return match ($this) {
            self::Content => AssetComponentEnum::Content->value,
        };
    }

    /**
     * @return class-string<FormConfigurator>
     */
    public function getFormClass(): string
    {
        return match ($this) {
            self::Content => CollectionForm::class,
        };
    }

    /**
     * @return class-string<Actionable>
     */
    public function getCreateActionClass(): string
    {
        return match ($this) {
            self::Content => CreateContentAction::class,
        };
    }

    /**
     * @return class-string<Actionable>
     */
    public function getDefaultDataActionClass(): string
    {
        return match ($this) {
            self::Content => MutateContentDataBeforeFillAction::class,
        };
    }

    public function hasTranslations(): bool
    {
        return match ($this) {
            self::Content => true,
        };
    }
}
