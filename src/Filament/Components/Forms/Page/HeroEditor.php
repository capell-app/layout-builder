<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Page;

use Capell\Admin\Enums\TinyEditorProfile;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Components\Forms\Editor\RichEditor;
use Capell\Admin\Filament\Components\Forms\Editor\TinyEditor;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\WidgetAsset;
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
                } else {
                    $page = $record->translatable;
                }

                if (! $page instanceof Pageable) {
                    return false;
                }

                return ! $this->hasPageBlockHeroAssets($page);
            })
            ->schema([
                ContentEditor::make('hero')
                    ->label(__('capell-layout-builder::form.hero'))
                    ->hint(__('capell-layout-builder::generic.hero_info'))
                    ->tap(
                        fn (ContentBuilder|RichEditor|TinyEditor $component): ContentBuilder|RichEditor|TinyEditor => $component instanceof TinyEditor
                            ? $component->profile(TinyEditorProfile::Simple->value)
                            : $component,
                    ),
            ]);
    }

    protected function hasPageBlockHeroAssets(Pageable $page): bool
    {
        $cache = cache();

        if (method_exists($cache, 'memo')) {
            $cache = $cache->memo();
        }

        return $cache->rememberForever(
            sprintf('page-%d-has-hero-block-assets', $page->id),
            function () use ($page): bool {
                /** @var class-string<WidgetAsset> $model */
                $model = WidgetAsset::class;

                return $model::query()
                    ->where('pageable_type', $page->getMorphClass())
                    ->where('pageable_id', $page->getKey())
                    ->whereHas('block', fn (Builder $query): Builder => $query->whereLike('key', 'hero%'))
                    ->exists();
            },
        );
    }
}
