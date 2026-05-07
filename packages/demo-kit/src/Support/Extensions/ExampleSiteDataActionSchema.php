<?php

declare(strict_types=1);

namespace Capell\DemoKit\Support\Extensions;

use Capell\Admin\Filament\Components\Forms\LanguageSelect;
use Capell\Admin\Filament\Components\Forms\SiteSelect;
use Filament\Forms\Components\TextInput;

final class ExampleSiteDataActionSchema
{
    /**
     * @return array<int, mixed>
     */
    public function schema(): array
    {
        return [
            TextInput::make('url')
                ->label(__('capell-admin::form.url'))
                ->default(config('app.url'))
                ->required(),
            LanguageSelect::make('languages')
                ->optionKey('code')
                ->multiple()
                ->withOptions(),
            SiteSelect::make('sites')
                ->optionKey('name')
                ->multiple(),
        ];
    }
}
