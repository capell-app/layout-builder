<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Spatie\LaravelData\Data;

final class ExampleInputData extends Data
{
    public function __construct(
        public string $title,
    ) {}

    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return ['title' => ['required', 'string', 'max:40']];
    }
}
