<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets;

use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaEnum;
use Capell\Layout\Filament\Components\Forms\HtmlClassInput;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class DefaultLayoutWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    protected static string $schemaType = SchemaEnum::LayoutWidget->value;

    public static function make(Schema $schema): array
    {
        return [
            Checkbox::make('hide_content')
                ->label(__('capell-admin::form.hide_content'))
                ->helperText(__('capell-admin::generic.hide_content_info')),
            HtmlClassInput::make('html_class'),
        ];
    }
}
