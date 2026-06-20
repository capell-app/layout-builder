<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\Widgets\PageAlertsFilamentWidget;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\Widget;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Actions\Action;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

it('hides the missing hero widget alert action when the user cannot update the layout', function (): void {
    test()->actingAsUser();

    $component = Livewire::test(PageAlertsFilamentWidget::class, [
        'record' => pageWithHeroContentAndLayoutWithoutHeroWidget(),
    ])->instance();

    assert($component instanceof PageAlertsFilamentWidget);

    $alert = $component->alerts()->get('missingHeroWidget');
    $action = pageAlertsWidgetAction($alert?->action);

    expect($action?->isVisible())->toBeFalse();
});

it('shows the missing hero widget alert action when the user can update the layout', function (): void {
    Permission::findOrCreate('Update:Layout', 'web');
    test()->actingAsUser();
    capell_test_instance(auth()->user(), User::class)->givePermissionTo('Update:Layout');

    $component = Livewire::test(PageAlertsFilamentWidget::class, [
        'record' => pageWithHeroContentAndLayoutWithoutHeroWidget(),
    ])->instance();

    assert($component instanceof PageAlertsFilamentWidget);

    $alert = $component->alerts()->get('missingHeroWidget');
    $action = pageAlertsWidgetAction($alert?->action);

    expect($action?->isVisible())->toBeTrue();
});

function pageAlertsWidgetAction(mixed $action): ?Action
{
    $firstAction = Arr::first(Arr::wrap($action));

    return $firstAction instanceof Action ? $firstAction : null;
}

function pageWithHeroContentAndLayoutWithoutHeroWidget(): Page
{
    Widget::factory()->create([
        'key' => 'page-content',
        'meta' => [
            'component' => 'capell.widget.page.content',
        ],
    ]);

    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'page-content'],
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
