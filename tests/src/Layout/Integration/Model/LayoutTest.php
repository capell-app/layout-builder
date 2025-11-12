<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Layout\Models\Widget;

it('has many widgets', function (): void {
    Widget::factory()->create(['key' => 'test']);

    $layout = Layout::factory()->create([
        'containers' => [
            'first' => ['widgets' => [['widget_key' => 'test']]],
            'second' => ['widgets' => []],
            'third' => ['widgets' => [['widget_key' => 'test2']]],
        ],
    ]);

    expect($layout->refresh())
        ->widgets->toBe(['test', 'test2']);
});
