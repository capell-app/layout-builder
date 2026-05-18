<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Widgets\PageAlertsWidget;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\Element;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

it('hides the missing hero widget alert action when the user cannot update the layout', function (): void {
    test()->actingAsUser();

    $component = Livewire::test(PageAlertsWidget::class, [
        'record' => pageWithHeroContentAndLayoutWithoutHeroWidget(),
    ])->instance();

    assert($component instanceof PageAlertsWidget);

    $alert = $component->alerts()->get('missingHeroWidget');

    expect($alert?->action?->isVisible())->toBeFalse();
});

it('shows the missing hero widget alert action when the user can update the layout', function (): void {
    Permission::findOrCreate('Update:Layout', 'web');
    test()->actingAsUser();
    auth()->user()->givePermissionTo('Update:Layout');

    $component = Livewire::test(PageAlertsWidget::class, [
        'record' => pageWithHeroContentAndLayoutWithoutHeroWidget(),
    ])->instance();

    assert($component instanceof PageAlertsWidget);

    $alert = $component->alerts()->get('missingHeroWidget');

    expect($alert?->action?->isVisible())->toBeTrue();
});

function pageWithHeroContentAndLayoutWithoutHeroWidget(): Page
{
    Element::factory()->create([
        'key' => 'page-content',
        'meta' => [
            'component' => 'capell.element.page.content',
        ],
    ]);

    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'elements' => [
                    ['element_key' => 'page-content'],
                ],
            ],
        ],
    ]);

    $page = Page::factory()->layout($layout)->create();

    Translation::factory()
        ->translatable($page)
        ->meta(['hero' => '<p>Hero copy</p>'])
        ->create();

    return $page;
}
