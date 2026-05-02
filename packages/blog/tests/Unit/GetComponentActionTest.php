<?php

declare(strict_types=1);

use Capell\Blog\Livewire\Page\Archive;
use Capell\Core\Actions\GetComponentClassAction;
use Capell\Tests\Support\Concerns\TestingFrontend;

uses(TestingFrontend::class);

it('returns component class for livewire component', function (): void {
    $component = 'capell-blog::page.archive';

    $componentClass = GetComponentClassAction::run($component, livewire: true);

    expect($componentClass)
        ->toBe(Archive::class);
});
