<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\Fixtures\Models\User;
use Capell\ThemeStudio\Admin\Actions\GenerateThemePreviewUrlAction;
use Capell\ThemeStudio\Admin\Actions\PublishThemeDraftAction;
use Capell\ThemeStudio\Admin\Actions\ResolveThemePublishLabelAction;
use Capell\ThemeStudio\Admin\Actions\StageThemeDraftAction;
use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Capell\ThemeStudio\Admin\Filament\Pages\ThemeStudioPage;
use Capell\ThemeStudio\Admin\Schemas\ThemeStudioSettingsSchema;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;
use Capell\ThemeStudio\Core\Exceptions\ThemePresetNotFoundException;
use Capell\ThemeStudio\Core\Rendering\BladeThemeRenderer;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Capell\Workspaces\Data\WorkspaceSettingsData;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;

function registerThemeStudioAdminTestTheme(): void
{
    $registry = resolve(ThemeRegistry::class);
    $registry->reset();

    $registry->register(
        new ThemeDefinitionData(
            key: 'corporate',
            name: 'Corporate',
            description: 'Trust-led',
            package: 'capell-app/theme-corporate',
            previewImage: '/preview.jpg',
            tags: ['Trust'],
            bestFit: ['B2B'],
            includedSections: ['hero'],
            presets: [
                new ThemePresetData(
                    key: 'boardroom',
                    name: 'Boardroom',
                    description: 'Formal',
                    previewImage: '/preview.jpg',
                ),
            ],
        ),
        new BladeThemeRenderer('corporate', 'missing-layout', []),
        [],
    );
}

it('registers a dedicated Theme Studio page', function (): void {
    expect(CapellAdmin::getExtraPages())->toContain(ThemeStudioPage::class)
        ->and(ThemeStudioPage::getNavigationLabel())->toBe('Theme Studio');
});

it('exposes settings through the theme studio group and schema', function (): void {
    $components = ThemeStudioSettingsSchema::make(Schema::make());

    expect(ThemeStudioSettings::group())->toBe('theme_studio')
        ->and($components)->not->toBeEmpty();
});

it('stages only registered presets before publishing the standalone draft', function (): void {
    registerThemeStudioAdminTestTheme();

    expect(fn (): ThemeStudioSettings => StageThemeDraftAction::run('corporate', 'missing'))
        ->toThrow(ThemePresetNotFoundException::class);

    $draft = StageThemeDraftAction::run('corporate', 'boardroom');

    expect($draft->draftTheme)->toBe('corporate')
        ->and($draft->draftPreset)->toBe('boardroom');

    $published = PublishThemeDraftAction::run();

    expect($published->activeTheme)->toBe('corporate')
        ->and($published->activePreset)->toBe('boardroom')
        ->and($published->draftTheme)->toBeNull()
        ->and($published->draftPreset)->toBeNull()
        ->and($published->draftWorkspaceId)->toBeNull();
});

it('delegates publishing to a workspace-aware publisher when one is bound', function (): void {
    registerThemeStudioAdminTestTheme();

    StageThemeDraftAction::run('corporate', 'boardroom');

    app()->bind(ThemeDraftPublisher::class, fn (): ThemeDraftPublisher => new class implements ThemeDraftPublisher
    {
        public function publish(ThemeStudioSettings $settings): ThemeStudioSettings
        {
            $settings->draftTheme = 'workspace:' . $settings->draftTheme;

            return $settings;
        }

        public function requiresApproval(): bool
        {
            return true;
        }
    });

    $published = PublishThemeDraftAction::run();

    expect($published->draftTheme)->toBe('workspace:corporate')
        ->and($published->activeTheme)->toBe('corporate');
});

it('submits staged theme drafts to Workspaces when Workspaces is installed', function (): void {
    registerThemeStudioAdminTestTheme();
    ensureThemeStudioWorkspacesTablesExist();

    CapellCore::registerPackage('capell-app/workspaces');
    CapellCore::forcePackageInstalled('capell-app/workspaces');
    $this->actingAs(User::factory()->create(), 'web');

    StageThemeDraftAction::run('corporate', 'boardroom');

    $published = PublishThemeDraftAction::run();
    $workspace = Workspace::query()->find($published->draftWorkspaceId);

    expect($workspace)->toBeInstanceOf(Workspace::class)
        ->and($workspace->status)->toBe(WorkspaceStatusEnum::InReview)
        ->and($workspace->name)->toBe('Theme Studio: corporate / boardroom')
        ->and($published->activeTheme)->toBe('corporate')
        ->and($published->activePreset)->toBe('boardroom')
        ->and($published->draftTheme)->toBe('corporate')
        ->and($published->draftPreset)->toBe('boardroom');
});

it('activates the staged theme draft when its workspace approval completes', function (): void {
    registerThemeStudioAdminTestTheme();
    ensureThemeStudioWorkspacesTablesExist();

    CapellCore::registerPackage('capell-app/workspaces');
    CapellCore::forcePackageInstalled('capell-app/workspaces');
    $user = User::factory()->create();
    $this->actingAs($user, 'web');

    StageThemeDraftAction::run('corporate', 'boardroom');

    $submitted = PublishThemeDraftAction::run();
    $workspace = Workspace::query()->findOrFail($submitted->draftWorkspaceId);
    $workspace->settings = new WorkspaceSettingsData(requiredApprovalLevels: 1);
    $workspace->save();

    $workspace->approve($user, 1, 'Theme draft approved.');

    $settings = resolve(ThemeStudioSettings::class);

    expect($settings->activeTheme)->toBe('corporate')
        ->and($settings->activePreset)->toBe('boardroom')
        ->and($settings->draftTheme)->toBeNull()
        ->and($settings->draftPreset)->toBeNull()
        ->and($settings->draftWorkspaceId)->toBeNull();
});

it('ignores approved workspaces that are not linked to the staged theme draft', function (): void {
    registerThemeStudioAdminTestTheme();
    ensureThemeStudioWorkspacesTablesExist();

    CapellCore::registerPackage('capell-app/workspaces');
    CapellCore::forcePackageInstalled('capell-app/workspaces');
    $user = User::factory()->create();
    $this->actingAs($user, 'web');

    StageThemeDraftAction::run('corporate', 'boardroom');

    $submitted = PublishThemeDraftAction::run();
    $unrelatedWorkspace = Workspace::query()->create([
        'name' => 'Unrelated review',
        'slug' => 'unrelated-review',
        'status' => WorkspaceStatusEnum::InReview,
        'settings' => new WorkspaceSettingsData(requiredApprovalLevels: 1),
    ]);

    $unrelatedWorkspace->approve($user, 1, 'Different workspace approved.');

    $settings = resolve(ThemeStudioSettings::class);

    expect($settings->activeTheme)->toBe('corporate')
        ->and($settings->activePreset)->toBe('boardroom')
        ->and($settings->draftTheme)->toBe('corporate')
        ->and($settings->draftPreset)->toBe('boardroom')
        ->and($settings->draftWorkspaceId)->toBe($submitted->draftWorkspaceId);
});

function ensureThemeStudioWorkspacesTablesExist(): void
{
    if (! DatabaseSchema::hasTable('versions')) {
        DatabaseSchema::create('versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->unsignedInteger('number')->default(1);
            $table->string('name')->nullable();
            $table->boolean('is_live')->default(false);
            $table->json('manifest')->nullable();
            $table->timestamps();
        });
    }

    if (! DatabaseSchema::hasTable('workspaces')) {
        DatabaseSchema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('color', 32)->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->string('kind', 32)->default('manual')->index();
            $table->unsignedBigInteger('base_version_id')->nullable()->index();
            $table->unsignedBigInteger('cloned_from_id')->nullable()->index();
            $table->json('settings')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('unpublish_at')->nullable()->index();
            $table->timestamp('embargo_until')->nullable()->index();
            $table->timestamp('review_reminder_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    if (! DatabaseSchema::hasTable('workspace_approvals')) {
        DatabaseSchema::create('workspace_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->index();
            $table->string('actionable_type')->nullable();
            $table->unsignedBigInteger('actionable_id')->nullable();
            $table->unsignedInteger('level')->default(1);
            $table->string('action', 32);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
}

it('uses standalone publishing labels and readiness by default', function (): void {
    registerThemeStudioAdminTestTheme();

    $page = resolve(ThemeStudioPage::class);
    $readiness = collect($page->readinessItems())->firstWhere('label', 'Whole-site preview');

    expect(ResolveThemePublishLabelAction::run())->toBe('Publish')
        ->and($page->publishLabel())->toBe('Publish')
        ->and($readiness['complete'])->toBeFalse()
        ->and($readiness['description'])->toBe('Stage a Theme Studio draft before publishing it.');
});

it('uses approval labels and readiness when the publisher requires approval', function (): void {
    registerThemeStudioAdminTestTheme();

    app()->bind(ThemeDraftPublisher::class, fn (): ThemeDraftPublisher => new class implements ThemeDraftPublisher
    {
        public function publish(ThemeStudioSettings $settings): ThemeStudioSettings
        {
            return $settings;
        }

        public function requiresApproval(): bool
        {
            return true;
        }
    });

    $page = resolve(ThemeStudioPage::class);
    $readiness = collect($page->readinessItems())->firstWhere('label', 'Whole-site preview');

    expect(ResolveThemePublishLabelAction::run())->toBe('Submit for approval')
        ->and($page->publishLabel())->toBe('Submit for approval')
        ->and($readiness['complete'])->toBeTrue()
        ->and($readiness['description'])->toBe('Workspaces approval is available for staged theme changes.');
});

it('generates signed whole-site preview URLs for a theme preset', function (): void {
    registerThemeStudioAdminTestTheme();

    $url = GenerateThemePreviewUrlAction::run('corporate', 'boardroom');

    expect($url)->toContain('__theme_preview=');
});

it('generates signed preview urls for a specific frontend path', function (): void {
    registerThemeStudioAdminTestTheme();

    $url = GenerateThemePreviewUrlAction::run('corporate', 'boardroom', '/services');

    expect($url)->toContain('/services?__theme_preview=');
});
