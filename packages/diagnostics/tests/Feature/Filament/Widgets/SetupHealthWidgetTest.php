<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Type;
use Capell\Diagnostics\Filament\Widgets\Health\SetupHealthWidgetAbstract as SetupHealthWidget;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Role;

uses(CreatesAdminUser::class)
    ->group('widget');

beforeEach(function (): void {
    Role::findOrCreate('super_admin');
    $this->adminUser = $this->createUser();
    $this->adminUser->assignRole('super_admin');
    $this->actingAs($this->adminUser);
});

it('renders while checks are incomplete', function (): void {
    livewire(SetupHealthWidget::class)->assertOk();
});

it('displays progress bar with percentage', function (): void {
    Site::factory()->create();

    livewire(SetupHealthWidget::class)
        ->assertSee('of')
        ->assertSee('complete');
});

it('shows failing items with fix actions', function (): void {
    livewire(SetupHealthWidget::class)
        ->assertSee('Items to complete:');
});

it('displays all green checks with 100 percent progress', function (): void {
    Site::factory()->create();
    Language::factory()->create();
    Type::factory()->create();
    Theme::factory()->create();

    livewire(SetupHealthWidget::class)
        ->assertSee('4 of 4 complete')
        ->assertSee('100%');
});

it('shows success message when all checks are green', function (): void {
    Site::factory()->create();
    Language::factory()->create();
    Type::factory()->create();
    Theme::factory()->create();

    livewire(SetupHealthWidget::class)
        ->assertSee('All setup requirements met!');
});

it('hides items section when all checks pass', function (): void {
    Site::factory()->create();
    Language::factory()->create();
    Type::factory()->create();
    Theme::factory()->create();

    livewire(SetupHealthWidget::class)
        ->assertDontSee('Items to complete:');
});

it('auto-hides when every check is green', function (): void {
    Site::factory()->create();
    Language::factory()->create();
    Type::factory()->create();
    Theme::factory()->create();

    expect(SetupHealthWidget::canView())->toBeFalse();
});

it('shows red icon for critical missing items', function (): void {
    livewire(SetupHealthWidget::class)
        ->assertSeeHtml('text-red-500');
});
