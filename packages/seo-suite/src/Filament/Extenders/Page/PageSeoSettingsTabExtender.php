<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Extenders\Page;

use Capell\Admin\Contracts\Extenders\PageSchemaExtender;
use Capell\Admin\Enums\PageTranslationSchemaHookEnum;
use Capell\Admin\Filament\Components\Forms\CacheTimeSelect;
use Capell\Admin\Filament\Components\Forms\PageRelationSelect;
use Capell\Admin\Filament\Support\HelperText;
use Capell\SeoSuite\Enums\RobotsDirectiveEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class PageSeoSettingsTabExtender implements PageSchemaExtender
{
    /**
     * @return array<int, Component>
     */
    public function extendSidebarComponents(Schema $configurator): array
    {
        return [];
    }

    /**
     * @return array<int, Component>
     */
    public function extendTranslationComponentsForHook(Schema $configurator, PageTranslationSchemaHookEnum $hook): array
    {
        return [];
    }

    /**
     * @param  array<int, mixed>  $relationManagers
     * @return array<int, mixed>
     */
    public function extendRelationManagers(Model $record, array $relationManagers): array
    {
        return $relationManagers;
    }

    /**
     * @param  array<int, mixed>  $tabs
     * @return array<int, mixed>
     */
    public function extendTabs(Schema $configurator, array $tabs): array
    {
        $tabs[] = Tab::make(__('capell-seo-suite::generic.seo_settings'))
            ->key('seo-settings')
            ->icon(Heroicon::OutlinedArrowTrendingUp)
            ->columns()
            ->schema([
                $this->getSeoSettingsSection($configurator),
            ]);

        return $tabs;
    }

    private function getSeoSettingsSection(Schema $schema): Section
    {
        return Section::make(__('capell-seo-suite::generic.seo_settings'))
            ->collapsible()
            ->compact()
            ->columnSpanFull()
            ->statePath('meta')
            ->icon(Heroicon::OutlinedArrowTrendingUp)
            ->columns(3)
            ->schema([
                PageRelationSelect::make('canonical_page_id')
                    ->setupRelation('canonicalPage', $schema)
                    ->qualifiedForeignKeyName('pages.id')
                    ->label(__('capell-seo-suite::form.canonical_page'))
                    ->helperText(__('capell-seo-suite::generic.canonical_page_info'))
                    ->saveRelationshipsUsing(fn (): false => false)
                    ->withHintEditAction()
                    ->dehydrated()
                    ->reactive(),
                CacheTimeSelect::make('cache_time'),
                Select::make('priority')
                    ->label(__('capell-seo-suite::form.priority'))
                    ->options(
                        collect(range(0, 9))
                            ->map(fn (int $priorityIndex): float => round(1.0 - $priorityIndex * 0.1, 1))
                            ->filter(fn (float $value): bool => $value >= 0.1)
                            ->mapWithKeys(function (float $value): array {
                                $formatted = number_format($value, 1);
                                if ($formatted === '1.0') {
                                    $label = $formatted . ' ' . __('capell-seo-suite::generic.highest');
                                } elseif ($formatted === '0.1') {
                                    $label = $formatted . ' ' . __('capell-seo-suite::generic.lowest');
                                } else {
                                    $label = $formatted;
                                }

                                return [$formatted => $label];
                            }),
                    ),
                HelperText::apply(
                    TextInput::make('canonical_url')
                        ->label(__('capell-seo-suite::form.canonical_url.label'))
                        ->url()
                        ->placeholder('https://...'),
                    'capell-seo-suite::form.canonical_url.helper',
                ),
                CheckboxList::make('robots')
                    ->options(RobotsDirectiveEnum::class)
                    ->descriptions(
                        collect(RobotsDirectiveEnum::cases())
                            ->mapWithKeys(fn (RobotsDirectiveEnum $directive): array => [$directive->value => $directive->getDescription()])
                            ->all(),
                    ),
                Textarea::make('meta_tags')
                    ->columnSpan(2)
                    ->rows(4)
                    ->label(__('capell-seo-suite::form.meta_tags'))
                    ->hint(__('capell-seo-suite::generic.meta_tags_extra')),
            ]);
    }
}
