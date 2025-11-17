<?php

declare(strict_types=1);

namespace Capell\Hero\Filament\Components\Forms\Page;

use Capell\Admin\Enums\TinyEditorProfile;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Components\Forms\Editor\RichEditor;
use Capell\Admin\Filament\Components\Forms\Editor\TinyEditor;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Layout\Enums\ModelEnum;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Builder;

class HeroEditor extends Group
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->statePath('meta')
            ->visible(
                function (null|PageTranslation|Page $record): bool {
                    if ($record === null) {
                        return false;
                    }

                    $page = $record instanceof Page ? $record : $record->page;

                    return ! $this->hasPageWidgetHeroAssets($page);
                },
            )
            ->schema([
                ContentEditor::make('hero')
                    ->label(__('capell-hero::form.hero'))
                    ->hint(__('capell-hero::generic.hero_info'))
                    ->tap(
                        fn (ContentBuilder|RichEditor|TinyEditor $component): ContentBuilder|RichEditor|TinyEditor => $component instanceof TinyEditor
                            ? $component->profile(TinyEditorProfile::Simple->value)
                            : $component,
                    ),
            ]);
    }

    protected function hasPageWidgetHeroAssets(Page $page): bool
    {
        return cache()->driver('array')->rememberForever(
            sprintf('page-%d-has-hero-widget-assets', $page->id),
            fn (): bool => CapellCore::getModel(ModelEnum::WidgetAsset)::where('page_id', $page->id)
                ->whereHas('widget', fn (Builder $query): Builder => $query->where('key', 'hero'))
                ->exists(),
        );
    }
}
