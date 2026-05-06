<?php

declare(strict_types=1);

use Capell\Admin\Enums\AlertTypeEnum;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Type;
use Capell\Diagnostics\Filament\Widgets\Health\AlertsWidgetAbstract as AlertsWidget;
use Capell\Installer\Providers\InstallerServiceProvider;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

uses(CreatesAdminUser::class)
    ->group('alerts');

function createAllTypes(): void
{
    foreach (TypeEnum::cases() as $enum) {
        Type::factory()->type($enum)->create();
    }
}

beforeEach(function (): void {
    Cache::driver('array')->clear();
    test()->actingAsAdmin();
    Site::query()->delete();
});

it('shows site missing warning when no sites exist', function (): void {
    $livewire = livewire(AlertsWidget::class);

    $livewire->assertSee(__('capell-admin::message.site_missing_warning'));

    $alerts = $livewire->get('alerts');
    expect($alerts)->toHaveKey('site');
});

it('shows type, theme, and language warnings when a site exists but none are configured', function (): void {
    $language = Language::factory()->create(['default' => false]);
    Site::factory()->language($language)->create();

    $livewire = livewire(AlertsWidget::class);

    $livewire->assertSee(__('capell-admin::message.type_missing_warning'))
        ->assertSee(__('capell-admin::message.theme_no_default_warning'))
        ->assertSee(__('capell-admin::message.language_no_default_warning'));

    $alerts = $livewire->get('alerts');
    expect($alerts)->toHaveKeys(['types', 'theme', 'language'])
        ->and($alerts['types']->type->value)->toBe(AlertTypeEnum::Warning->value)
        ->and($alerts['theme']->type->value)->toBe(AlertTypeEnum::Warning->value)
        ->and($alerts['language']->type->value)->toBe(AlertTypeEnum::Warning->value);
});

it('shows only language warning when default theme & site exist but no default language', function (): void {
    createAllTypes();
    $themeType = Type::query()->where('type', TypeEnum::Theme)->first();
    Theme::factory()->state(['type_id' => $themeType?->id, 'default' => true])->create();
    $language = Language::factory()->create(['default' => false]);
    Site::factory()->language($language)->create();

    $livewire = livewire(AlertsWidget::class);

    $livewire->assertSee(__('capell-admin::message.language_no_default_warning'))
        ->assertDontSee(__('capell-admin::message.type_missing_warning'))
        ->assertDontSee(__('capell-admin::message.theme_missing_warning'));

    $alerts = $livewire->get('alerts');
    expect($alerts)->toHaveKey('language')
        ->and($alerts)->not()->toHaveKeys(['types', 'theme'])
        ->and($alerts['language']->type->value)->toBe(AlertTypeEnum::Warning->value);
});

it('shows only theme warning when default language & site exist but no default theme', function (): void {
    createAllTypes();
    Language::factory()->default()->create();
    $themeType = Type::query()->where('type', TypeEnum::Theme)->first();
    $theme = Theme::factory()->state(['type_id' => $themeType?->id, 'default' => false])->createQuietly();
    Site::factory()->theme($theme)->create();

    $livewire = livewire(AlertsWidget::class);

    $livewire->assertSee(__('capell-admin::message.theme_no_default_warning'))
        ->assertDontSee(__('capell-admin::message.type_missing_warning'))
        ->assertDontSee(__('capell-admin::message.language_missing_warning'));

    $alerts = $livewire->get('alerts');
    expect($alerts)->toHaveKey('theme')
        ->and($alerts)->not()->toHaveKeys(['types', 'language'])
        ->and($alerts['theme']->type->value)->toBe(AlertTypeEnum::Warning->value);
});

it('has create language action with correct URL', function (): void {
    $language = Language::factory()->create(['default' => false]);
    Site::factory()->language($language)->create();

    $widget = new AlertsWidget;
    $action = $widget->getAction('createLanguage');

    expect($action)->not()->toBeNull();
});

it('has create theme action with correct URL', function (): void {
    $language = Language::factory()->create(['default' => false]);
    Site::factory()->language($language)->create();
    Type::factory()->theme()->create();

    $widget = new AlertsWidget;
    $action = $widget->getAction('createTheme');

    expect($action)->not()->toBeNull();
});

it('does not show resource alerts when all defaults exist', function (): void {
    createAllTypes();
    $themeType = Type::query()->where('type', TypeEnum::Theme)->first();
    Theme::factory()->state(['type_id' => $themeType?->id, 'default' => true])->create();
    Language::factory()->default()->create();
    Site::factory()->create();

    $livewire = livewire(AlertsWidget::class);
    $livewire->assertDontSee(__('capell-admin::message.type_missing_warning'))
        ->assertDontSee(__('capell-admin::message.theme_missing_warning'))
        ->assertDontSee(__('capell-admin::message.language_missing_warning'));
});

it('shows installer alert only when installer package is present', function (): void {
    createAllTypes();
    $themeType = Type::query()->where('type', TypeEnum::Theme)->first();
    Theme::factory()->state(['type_id' => $themeType?->id, 'default' => true])->create();
    Language::factory()->default()->create();
    Site::factory()->create();

    $livewire = livewire(AlertsWidget::class);

    $alerts = $livewire->get('alerts');

    if (class_exists(InstallerServiceProvider::class)) {
        $livewire->assertSee(__('capell-admin::message.installer_present_warning'));
        expect($alerts)->toHaveKey('installer');

        return;
    }

    $livewire->assertDontSee(__('capell-admin::message.installer_present_warning'));
    expect($alerts)->not->toHaveKey('installer');
});

it('adds installer actions only when installer package is present', function (): void {
    createAllTypes();
    $themeType = Type::query()->where('type', TypeEnum::Theme)->first();
    Theme::factory()->state(['type_id' => $themeType?->id, 'default' => true])->create();
    Language::factory()->default()->create();
    Site::factory()->create();

    $alerts = livewire(AlertsWidget::class)->get('alerts');
    $actionNames = collect(Arr::wrap($alerts['installer']->action ?? []))
        ->map(fn (mixed $action): ?string => method_exists($action, 'getName') ? $action->getName() : null)
        ->all();

    if (class_exists(InstallerServiceProvider::class)) {
        expect($actionNames)
            ->toContain('viewInstaller', 'deleteInstaller');

        return;
    }

    expect($actionNames)
        ->not->toContain('viewInstaller', 'deleteInstaller');
});
