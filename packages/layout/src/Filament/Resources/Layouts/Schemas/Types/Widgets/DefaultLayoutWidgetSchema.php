<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaExtenderEnum;
use Capell\Layout\Enums\SchemaTypeEnum;
use Capell\Layout\Filament\Components\Forms\HtmlClassInput;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;

class DefaultLayoutWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = SchemaTypeEnum::LayoutWidget;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutWidget->value);
    }

    public function make(Schema $schema): array
    {
        return [
            Checkbox::make('show_page_title')
                ->label(__('capell-admin::form.show_page_title'))
                ->helperText(__('capell-admin::generic.show_page_title_info')),
            Checkbox::make('show_page_content')
                ->label(__('capell-admin::form.show_page_content'))
                ->helperText(__('capell-admin::generic.show_page_content_info')),
            HtmlClassInput::make('html_class'),
        ];
    }
}
