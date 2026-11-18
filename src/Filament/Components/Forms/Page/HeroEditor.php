<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Components\Forms\Page;

use Capell\Admin\Enums\TinyEditorProfile;
use Capell\Admin\Filament\Components\Forms\ContentEditor;
use Capell\Admin\Filament\Components\Forms\Editor\ContentBuilder;
use Capell\Admin\Filament\Components\Forms\Editor\RichEditor;
use Capell\Admin\Filament\Components\Forms\Editor\TinyEditor;
use Capell\Admin\Filament\Support\HelperText;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Filament\Actions\Action;
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

                $page = $record instanceof Pageable ? $record : $record->translatable;

                if (! $page instanceof Pageable) {
                    return false;
                }

                return ! $this->hasPageWidgetHeroAssets($page);
            })
            ->schema([
                ContentEditor::make('hero')
                    ->label(__('capell-layout-builder::form.hero'))
                    ->helperText(fn (): ?string => HelperText::enabled() ? (string) __('capell-layout-builder::generic.hero_info') : null)
                    ->hintAction(
                        Action::make('heroGuidance')
                            ->label(__('capell-layout-builder::generic.hero_guidance_action'))
                            ->icon('heroicon-m-question-mark-circle')
                            ->link()
                            ->visible(fn (): bool => HelperText::enabled())
                            ->modalHeading(__('capell-layout-builder::generic.hero_guidance_heading'))
                            ->modalDescription(__('capell-layout-builder::generic.hero_guidance_body'))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel(__('capell-layout-builder::generic.hero_guidance_close')),
                    )
                    ->tap(
                        fn (ContentBuilder|RichEditor|TinyEditor $component): ContentBuilder|RichEditor|TinyEditor => $component instanceof TinyEditor
                            ? $component->profile(TinyEditorProfile::Simple->value)
                            : $component,
                    ),
            ]);
    }

    protected function hasPageWidgetHeroAssets(Pageable $page): bool
    {
        $cache = cache();

        if (method_exists($cache, 'memo')) {
            $cache = $cache->memo();
        }

        return $cache->rememberForever(
            sprintf('page-%d-has-hero-widget-assets', $page->id),
            function () use ($page): bool {
                /** @var class-string<WidgetAsset> $model */
                $model = WidgetAsset::class;

                return $model::query()
                    ->where('pageable_type', $page->getMorphClass())
                    ->where('pageable_id', $page->getKey())
                    ->whereHas('widget', fn (Builder $query): Builder => $query->whereLike('key', 'hero%'))
                    ->exists();
            },
        );
    }
}
