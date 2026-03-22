<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Layouts\Schemas\Types\Widgets;

use Capell\Admin\Contracts\SchemaTypeEnumInterface;
use Capell\Admin\Contracts\TypeSchemaInterface;
use Capell\Admin\Filament\Concerns\HasTypeSchema;
use Capell\Layout\Enums\SchemaExtenderEnum;
use Capell\Layout\Enums\TypeSchemaEnum;
use Capell\Layout\Filament\Components\Forms\HtmlClassInput;
use Capell\Layout\Filament\Components\Forms\Widget\ResultsOverrideSchema;
use Filament\Schemas\Schema;

class ResultsLayoutWidgetSchema implements TypeSchemaInterface
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
            ...ResultsOverrideSchema::make($schema),
            HtmlClassInput::make('html_class'),
        ];
    }
}
