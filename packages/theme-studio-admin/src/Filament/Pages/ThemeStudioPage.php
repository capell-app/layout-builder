<?php

declare(strict_types=1);

namespace Capell\ThemeStudio\Admin\Filament\Pages;

use BackedEnum;
use Capell\ThemeStudio\Admin\Actions\GenerateThemePreviewUrlAction;
use Capell\ThemeStudio\Admin\Actions\PublishThemeDraftAction;
use Capell\ThemeStudio\Admin\Actions\ResolveThemePublishingReadinessAction;
use Capell\ThemeStudio\Admin\Actions\ResolveThemePublishLabelAction;
use Capell\ThemeStudio\Admin\Actions\StageThemeDraftAction;
use Capell\ThemeStudio\Admin\Contracts\ThemeDraftPublisher;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ThemeStudioPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $slug = 'theme-studio';

    protected static ?int $navigationSort = 8;

    protected string $view = 'capell-theme-studio-admin::filament.pages.theme-studio';

    public static function getNavigationLabel(): string
    {
        return __('capell-theme-studio-admin::studio.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_administration');
    }

    public function getTitle(): string|Htmlable
    {
        return __('capell-theme-studio-admin::studio.title');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function themeCards(): array
    {
        $settings = resolve(ThemeStudioSettings::class);

        return collect(resolve(ThemeRegistry::class)->definitions())
            ->map(fn (ThemeDefinitionData $definition): array => [
                'key' => $definition->key,
                'name' => $definition->name,
                'description' => $definition->description,
                'package' => $definition->package,
                'previewImage' => $definition->previewImage,
                'tags' => $definition->tags,
                'bestFit' => $definition->bestFit,
                'includedSections' => $definition->includedSections,
                'presets' => $definition->presets,
                'active' => $settings->activeTheme === $definition->key,
                'draft' => $settings->draftTheme === $definition->key,
                'activePreset' => $settings->activePreset,
                'draftPreset' => $settings->draftPreset,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, complete: bool, description: string}>
     */
    public function readinessItems(): array
    {
        $settings = resolve(ThemeStudioSettings::class);
        $themeCount = count(resolve(ThemeRegistry::class)->definitions());
        $publishingReadiness = ResolveThemePublishingReadinessAction::run($settings);

        return [
            [
                'label' => __('capell-theme-studio-admin::studio.readiness.themes'),
                'description' => trans_choice('capell-theme-studio-admin::studio.readiness.themes_description', $themeCount, ['count' => $themeCount]),
                'complete' => $themeCount > 0,
            ],
            [
                'label' => __('capell-theme-studio-admin::studio.readiness.brand'),
                'description' => __('capell-theme-studio-admin::studio.readiness.brand_description'),
                'complete' => filled($settings->brandProfile['primaryColor'] ?? null) && filled($settings->brandProfile['accentColor'] ?? null),
            ],
            [
                'label' => __('capell-theme-studio-admin::studio.readiness.preview'),
                'description' => $publishingReadiness['description'],
                'complete' => $publishingReadiness['complete'],
            ],
        ];
    }

    public function publishLabel(): string
    {
        return ResolveThemePublishLabelAction::run();
    }

    public function publishNotificationTitle(): string
    {
        if (resolve(ThemeDraftPublisher::class)->requiresApproval()) {
            return __('capell-theme-studio-admin::studio.notifications.draft_submitted');
        }

        return __('capell-theme-studio-admin::studio.notifications.draft_published');
    }

    public function previewUrl(string $themeKey, string $presetKey): string
    {
        return GenerateThemePreviewUrlAction::run($themeKey, $presetKey, '/');
    }

    public function stageTheme(string $themeKey, string $presetKey): void
    {
        StageThemeDraftAction::run($themeKey, $presetKey);

        Notification::make('theme-studio-draft-staged')
            ->success()
            ->title(__('capell-theme-studio-admin::studio.notifications.draft_staged'))
            ->send();
    }

    public function publishDraft(): void
    {
        PublishThemeDraftAction::run();

        Notification::make('theme-studio-draft-published')
            ->success()
            ->title($this->publishNotificationTitle())
            ->send();
    }
}
