<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetRenderingMetadataInteger;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetRenderingMetadataString;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetRenderingMetadataUnit;

it('persists rendering metadata values without leaking them back into metadata', function (mixed $value, ?string $expected): void {
    $record = Widget::factory()->create();
    $record->meta = [
        'component' => $value,
        'component_item' => $value,
        'view_file' => $value,
        'livewire' => true,
        'retained' => 'saved metadata',
    ];
    $record->save();

    $reloaded = Widget::query()->whereKey($record->getKey())->sole();

    expect($reloaded->component)->toBe($expected)
        ->and($reloaded->component_item)->toBe($expected)
        ->and($reloaded->view_file)->toBe($expected)
        ->and($reloaded->is_livewire)->toBeTrue()
        ->and($reloaded->meta)->toBe(['retained' => 'saved metadata']);

    expect($reloaded->getMetaComponent())->toBe($expected)
        ->and($reloaded->getComponentItem())->toBe($expected)
        ->and($reloaded->getViewFile())->toBe($expected)
        ->and($reloaded->getMetaComponentType())->toBe('livewire');
})->with([
    'string backed enum' => [WidgetRenderingMetadataString::Component, 'rendered.component'],
    'blank backed enum' => [WidgetRenderingMetadataString::Blank, null],
    'integer backed enum' => [WidgetRenderingMetadataInteger::Component, '42'],
    'zero backed enum' => [WidgetRenderingMetadataInteger::Zero, '0'],
    'scalar string' => ['  rendered.component  ', 'rendered.component'],
    'scalar integer' => [42, '42'],
    'scalar zero' => [0, '0'],
    'scalar float' => [12.5, '12.5'],
    'scalar true' => [true, '1'],
    'scalar false' => [false, null],
    'null' => [null, null],
    'blank string' => ['   ', null],
    'unit enum' => [WidgetRenderingMetadataUnit::Component, null],
    'array' => [['component'], null],
    'arbitrary object' => [static fn (): stdClass => new stdClass, null],
    'stringable object' => [static fn (): Stringable => new class implements Stringable
    {
        #[Override]
        public function __toString(): string
        {
            return 'rendered.component';
        }
    }, null],
]);

it('extracts distinct rendering fields and leaves no empty rendering metadata behind', function (): void {
    $record = Widget::factory()->create(['meta' => [
        'component' => WidgetRenderingMetadataString::Component,
        'component_item' => WidgetRenderingMetadataInteger::Component,
        'view_file' => '  cards/article.blade.php  ',
        'livewire' => false,
    ]]);
    $reloaded = Widget::query()->whereKey($record->getKey())->sole();

    expect($reloaded->component)->toBe('rendered.component')
        ->and($reloaded->component_item)->toBe('42')
        ->and($reloaded->view_file)->toBe('cards/article.blade.php')
        ->and($reloaded->is_livewire)->toBeFalse()
        ->and($reloaded->meta)->toBeNull();
});
