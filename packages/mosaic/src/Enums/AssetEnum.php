<?php

declare(strict_types=1);

namespace Capell\Mosaic\Enums;

use BackedEnum;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Contracts\Actionable;
use Capell\Mosaic\Actions\CreateContentAction;
use Capell\Mosaic\Actions\MutateContentDataBeforeFillAction;
use Capell\Mosaic\Filament\Resources\Sections\Schemas\SectionForm;
use Capell\Mosaic\Models\Section;
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
            self::Content => config('capell-mosaic.assets.content.color', 'info'),
        };
    }

    public function getIcon(): string|BackedEnum
    {
        return match ($this) {
            self::Content => config('capell-mosaic.assets.content.icon', Heroicon::OutlinedFolder),
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
            self::Content => config('capell-mosaic.assets.content.model', Section::class),
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
            self::Content => SectionForm::class,
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
