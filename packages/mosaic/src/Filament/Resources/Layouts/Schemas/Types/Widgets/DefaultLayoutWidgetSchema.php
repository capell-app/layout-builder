<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Layouts\Schemas\Types\Widgets;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Mosaic\Enums\SchemaExtenderEnum;
use Capell\Mosaic\Enums\TypeSchemaEnum;
use Capell\Mosaic\Filament\Components\Forms\HtmlClassInput;
use Filament\Schemas\Schema;

class DefaultLayoutWidgetSchema implements TypeSchemaInterface
{
    use HasTypeSchema;

    public static SchemaTypeEnumInterface $schemaType = TypeSchemaEnum::LayoutWidget;

    public static function getExtenders(): iterable
    {
        return app()->tagged(SchemaExtenderEnum::LayoutWidget->value);
    }

    public function make(Schema $schema): array
    {
        return [
            HtmlClassInput::make('html_class'),
        ];
    }
}
