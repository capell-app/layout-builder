<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Components\Forms\Page;

use Capell\Admin\Enums\TinyEditorProfile;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Components\Forms\Editor\RichEditor;
use Capell\Admin\Filament\Components\Forms\Editor\TinyEditor;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Translation;
use Capell\Mosaic\Enums\ModelEnum;
use Capell\Mosaic\Models\WidgetAsset;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Builder;

class HeroEditor extends Group
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->statePath('meta')
            ->visible(function (null|Translation|Pageable $record): bool {
                if ($record === null) {
                    return false;
                }

                $page = null;
                if ($record instanceof Pageable) {
                    $page = $record;
                } elseif ($record instanceof Translation) {
                    $page = $record->translatable;
                } elseif (method_exists($record, 'pageable')) {
                    $page = $record->pageable;
                }

                if (! $page instanceof Pageable) {
                    return false;
                }

                return ! $this->hasPageWidgetHeroAssets($page);
            })
            ->schema([
                ContentEditor::make('hero')
                    ->label(__('capell-mosaic::form.hero'))
                    ->hint(__('capell-mosaic::generic.hero_info'))
                    ->tap(
                        fn (ContentBuilder|RichEditor|TinyEditor $component): ContentBuilder|RichEditor|TinyEditor => $component instanceof TinyEditor
                            ? $component->profile(TinyEditorProfile::Simple->value)
                            : $component,
                    ),
            ]);
    }

    protected function hasPageWidgetHeroAssets(Pageable $page): bool
    {
        return cache()->memo()->rememberForever(
            sprintf('page-%d-has-hero-widget-assets', $page->id),
            function () use ($page): bool {
                /** @var class-string<WidgetAsset> $model */
                $model = CapellCore::getModel(ModelEnum::WidgetAsset);

                return $model::query()
                    ->where('pageable_type', $page->getMorphClass())
                    ->where('pageable_id', $page->getKey())
                    ->whereHas('widget', fn (Builder $query): Builder => $query->whereLike('key', 'hero%'))
                    ->exists();
            },
        );
    }
}
