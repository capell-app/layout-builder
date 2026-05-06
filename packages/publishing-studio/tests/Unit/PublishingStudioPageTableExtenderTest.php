<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\PageTableExtender;
use Capell\PublishingStudio\Extenders\PublishingStudioPageTableExtender;
use Illuminate\Database\Eloquent\Builder;

it('implements PageTableExtender', function (): void {
    expect(PublishingStudioPageTableExtender::class)
        ->toImplement(PageTableExtender::class);
});

it('modifyQuery removes WorkspaceContextScope', function (): void {
    $extender = new PublishingStudioPageTableExtender;
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('withoutGlobalScope')
        ->once()
        ->andReturnSelf();

    $result = $extender->modifyQuery($query);

    expect($result)->toBe($query);
});
