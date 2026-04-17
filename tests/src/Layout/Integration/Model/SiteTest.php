<?php

declare(strict_types=1);

// tests/Integration/Models/SiteTest.php

use Capell\Core\Models\Site;

it('has many contents', function (): void {
    $site = Site::factory()->create();
    $content = Collection::factory()->create(['site_id' => $site->id]);

    expect($site->contents->pluck('id'))->toContain($content->id);
});
