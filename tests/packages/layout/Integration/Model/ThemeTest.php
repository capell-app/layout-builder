<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Theme;

it('has many layouts', function (): void {
    $theme = Theme::factory()->create();
    $layout = Layout::factory()->create(['theme_id' => $theme->id]);

    expect($theme->layouts->pluck('id'))->toContain($layout->id);
});
