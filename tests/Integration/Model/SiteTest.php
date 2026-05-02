<?php

declare(strict_types=1);

// tests/Integration/Models/SiteTest.php

use Capell\Core\Models\Site;
use Capell\Mosaic\Models\Section;

it('has many sections', function (): void {
    $site = Site::factory()->create();
    $section = Section::factory()->create(['site_id' => $site->id]);

    expect($site->sections->pluck('id'))->toContain($section->id);
});
