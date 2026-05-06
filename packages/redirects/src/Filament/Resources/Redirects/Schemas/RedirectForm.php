<?php

declare(strict_types=1);

namespace Capell\Redirects\Filament\Resources\Redirects\Schemas;

use Capell\Admin\Data\Configurators\ConfiguratorContextData;
use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Capell\Admin\Filament\Components\Forms\StatusToggle;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Redirects\Actions\ValidateRedirectAction;
use Closure;
use Filament\FormBuilder\Components\Hidden;
use Filament\FormBuilder\Components\Radio;
use Filament\FormBuilder\Components\Textarea;
use Filament\FormBuilder\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class RedirectForm implements FormConfigurator
{
    public static function configure(Schema $schema, ?ConfiguratorContextData $context = null): Schema
    {
        return $schema->components(static::getFormSchema($schema))
            ->columns();
    }

    protected static function getFormSchema(Schema $schema): array
    {
        return [
            SiteSelect::make('site_id')
                ->reactive()
                ->required()
                ->afterStateUpdated(function (Set $set): void {
                    $set('language_id', null);
                }),

            LanguageSelect::make('language_id')
                ->withRelationship()
                ->modifyRelationQueryUsing(
                    fn (Builder $query, Get $get): Builder => $query->when(
                        $get('site_id'),
                        fn (Builder $query, int $siteId): Builder => $query->whereHas(
                            'sites',
                            fn (BuilderContract $query): BuilderContract => $query->where('sites.id', $siteId),
                        ),
                    ),
                )
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        $siteId = $get('site_id');

                        if (blank($value) || blank($siteId)) {
                            return;
                        }

                        $languageBelongsToSite = Site::query()
                            ->whereKey((int) $siteId)
                            ->whereHas(
                                'languages',
                                fn (Builder $query): Builder => $query->whereKey((int) $value),
                            )
                            ->exists();

                        if (! $languageBelongsToSite) {
                            $fail(__('redirects::message.site_language_not_accessible'));
                        }
                    },
                ])
                ->disabled(fn (Get $get): bool => $get('site_id') === null || $get('site_id') === 0)
                ->required()
                ->reactive(),

            TextInput::make('url')
                ->label(__('redirects::form.source_url'))
                ->required()
                ->placeholder('/')
                ->rules([
                    fn (): Closure => function (string $attribute, ?string $value, Closure $fail): void {
                        if ($value === null || $value === '') {
                            return;
                        }

                        if (! str_starts_with($value, '/')) {
                            $fail(__('redirects::message.redirect_source_must_start_with_slash'));
                        }
                    },
                ])
                ->unique(
                    table: PageUrl::class,
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                        ->withoutTrashed()
                        ->where('language_id', $get('language_id'))
                        ->where('site_id', $get('site_id')),
                )
                ->columnSpanFull(),

            TextInput::make('target_url')
                ->label(__('redirects::form.target_url'))
                ->required()
                ->placeholder(__('redirects::form.target_url_placeholder'))
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, ?string $value, Closure $fail) use ($get): void {
                        if ($value === null || $value === '' || $get('url') === null) {
                            return;
                        }

                        if ($value === $get('url')) {
                            $fail(__('redirects::message.redirect_self_redirect'));
                        }

                        $result = ValidateRedirectAction::run(
                            sourceUrl: (string) $get('url'),
                            targetUrl: $value,
                            siteId: (int) $get('site_id'),
                            languageId: (int) $get('language_id'),
                            statusCode: null,
                            validateDuplicateSource: false,
                        );

                        foreach ($result['errors'] as $error) {
                            $fail($error);
                        }
                    },
                ])
                ->columnSpanFull(),

            Radio::make('status_code')
                ->label(__('redirects::form.status_code'))
                ->options(RedirectStatusCodeEnum::class)
                ->default(RedirectStatusCodeEnum::Permanent)
                ->inline()
                ->required(),

            Textarea::make('notes')
                ->label(__('redirects::form.notes'))
                ->rows(3)
                ->columnSpanFull(),

            StatusToggle::make('status'),

            Hidden::make('type')
                ->default(UrlTypeEnum::Redirect->value),

            Hidden::make('is_manual')
                ->default(true),
        ];
    }
}
