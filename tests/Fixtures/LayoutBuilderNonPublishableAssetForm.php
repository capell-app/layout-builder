<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Admin\Filament\Contracts\FormConfigurator;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LayoutBuilderNonPublishableAssetForm implements FormConfigurator
{
    public static function configure(Schema $configurator, mixed $context = null): Schema
    {
        return $configurator->schema([
            TextInput::make('title'),
        ]);
    }
}
