<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Filament\Components\Forms\Site;

use Capell\Core\Models\Translation;
use Capell\SeoSuite\Enums\AiDiscoveryStatusEnum;
use Capell\SeoSuite\Filament\Components\Forms\SearchMetaDataSection;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

class TranslationMetaSchema
{
    public static function make(array $components = []): array
    {
        return [
            Group::make()
                ->statePath('meta')
                ->schema([
                    SearchMetaDataSection::make()
                        ->schema([
                            TextInput::make('title_after_text')
                                ->label(__('capell-admin::form.meta_title_after_text'))
                                ->placeholder(
                                    fn (?Translation $record): string => __(
                                        'capell-admin::generic.meta_title_after_text',
                                        ['site' => $record?->title ?? config('app.name')],
                                    ),
                                ),
                            Textarea::make('description')
                                ->label(__('capell-admin::form.description'))
                                ->helperText(__('capell-admin::generic.site_default_meta_data'))
                                ->rows(2)
                                ->maxLength(250),
                        ]),
                    Textarea::make('footer_copy')
                        ->label(__('capell-admin::form.footer_copy'))
                        ->default('&copy; :year :name')
                        ->rows(3)
                        ->helperText(__('capell-admin::generic.footer_copy_info')),
                    Section::make(__('capell-seo-suite::generic.ai_discovery'))
                        ->compact()
                        ->collapsible()
                        ->columns(3)
                        ->schema([
                            Checkbox::make('ai_discovery.llms_txt_enabled')
                                ->label(__('capell-seo-suite::form.ai_discovery_llms_txt_enabled'))
                                ->default(true),
                            Checkbox::make('ai_discovery.llms_full_txt_enabled')
                                ->label(__('capell-seo-suite::form.ai_discovery_llms_full_txt_enabled'))
                                ->default(false),
                            Checkbox::make('ai_discovery.markdown_pages_enabled')
                                ->label(__('capell-seo-suite::form.ai_discovery_markdown_pages_enabled'))
                                ->default(true),
                            Checkbox::make('ai_discovery.accept_markdown_enabled')
                                ->label(__('capell-seo-suite::form.ai_discovery_accept_markdown_enabled'))
                                ->default(false),
                            Checkbox::make('ai_discovery.default_include_pages')
                                ->label(__('capell-seo-suite::form.ai_discovery_default_include_pages'))
                                ->default(true),
                            Select::make('ai_discovery.status')
                                ->label(__('capell-seo-suite::form.ai_discovery_status'))
                                ->options(AiDiscoveryStatusEnum::class)
                                ->default(AiDiscoveryStatusEnum::Enabled->value),
                            TextInput::make('ai_discovery.default_section')
                                ->label(__('capell-seo-suite::form.ai_discovery_default_section'))
                                ->default('Pages'),
                            TextInput::make('ai_discovery.max_full_txt_pages')
                                ->label(__('capell-seo-suite::form.ai_discovery_max_full_txt_pages'))
                                ->numeric()
                                ->minValue(0)
                                ->default(50),
                            TextInput::make('ai_discovery.max_full_txt_bytes')
                                ->label(__('capell-seo-suite::form.ai_discovery_max_full_txt_bytes'))
                                ->numeric()
                                ->minValue(0)
                                ->default(250000),
                            TextInput::make('ai_discovery.cache_ttl_seconds')
                                ->label(__('capell-seo-suite::form.ai_discovery_cache_ttl_seconds'))
                                ->numeric()
                                ->minValue(0)
                                ->default(3600),
                            Textarea::make('ai_discovery.intro_markdown')
                                ->label(__('capell-seo-suite::form.ai_discovery_intro_markdown'))
                                ->rows(4)
                                ->columnSpanFull(),
                        ]),
                    ...$components,
                ]),
        ];
    }
}
