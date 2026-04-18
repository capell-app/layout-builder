<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\PageHeaderActionExtender;
use Capell\Admin\Contracts\Extenders\SiteHeaderActionExtender;
use Capell\Assistant\Filament\Actions\AiCreatorAction;
use Capell\Assistant\Support\Admin\AiCreatorPageExtender;
use Capell\Assistant\Support\Admin\AiCreatorSiteExtender;

it('AiCreatorPageExtender implements PageHeaderActionExtender', function (): void {
    expect(new AiCreatorPageExtender)->toBeInstanceOf(PageHeaderActionExtender::class);
});

it('AiCreatorPageExtender returns one AiCreatorAction', function (): void {
    $actions = (new AiCreatorPageExtender)->actions();

    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(AiCreatorAction::class);
});

it('AiCreatorSiteExtender implements SiteHeaderActionExtender', function (): void {
    expect(new AiCreatorSiteExtender)->toBeInstanceOf(SiteHeaderActionExtender::class);
});

it('AiCreatorSiteExtender returns one AiCreatorAction', function (): void {
    $actions = (new AiCreatorSiteExtender)->actions();

    expect($actions)->toHaveCount(1)
        ->and($actions[0])->toBeInstanceOf(AiCreatorAction::class);
});
