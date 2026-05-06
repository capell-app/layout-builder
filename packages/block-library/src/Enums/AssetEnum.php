<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Enums;

use BackedEnum;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\BlockLibrary\Actions\CreateContentAction;
use Capell\BlockLibrary\Actions\MutateContentDataBeforeFillAction;
use Capell\BlockLibrary\Filament\Resources\BlockLibrary\Schemas\ContentBlockForm;
use Capell\BlockLibrary\Models\ContentBlock;
use Capell\Core\Contracts\Actionable;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

enum AssetEnum: string implements HasColor, HasIcon, HasLabel
{
    case ContentBlock = 'content_block';

    public function getColor(): string
    {
        return match ($this) {
            self::ContentBlock => config('capell-block-library.assets.content_block.color', 'info'),
        };
    }

    public function getIcon(): string|BackedEnum
    {
        return match ($this) {
            self::ContentBlock => config('capell-block-library.assets.content_block.icon', Heroicon::OutlinedClipboardDocumentList),
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ContentBlock => __('capell-admin::generic.content'),
        };
    }

    /**
     * @return class-string<Model>
     */
    public function getModel(): string
    {
        return match ($this) {
            self::ContentBlock => config('capell-block-library.assets.content_block.model', ContentBlock::class),
        };
    }

    public function getComponent(): string
    {
        return match ($this) {
            self::ContentBlock => AssetComponentEnum::ContentBlock->value,
        };
    }

    /**
     * @return class-string<FormConfigurator>
     */
    public function getFormClass(): string
    {
        return match ($this) {
            self::ContentBlock => ContentBlockForm::class,
        };
    }

    /**
     * @return class-string<Actionable>
     */
    public function getCreateActionClass(): string
    {
        return match ($this) {
            self::ContentBlock => CreateContentAction::class,
        };
    }

    /**
     * @return class-string<Actionable>
     */
    public function getDefaultDataActionClass(): string
    {
        return match ($this) {
            self::ContentBlock => MutateContentDataBeforeFillAction::class,
        };
    }

    public function hasTranslations(): bool
    {
        return match ($this) {
            self::ContentBlock => true,
        };
    }
}
