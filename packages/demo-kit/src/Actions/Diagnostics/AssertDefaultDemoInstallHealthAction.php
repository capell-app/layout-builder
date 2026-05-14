<?php

declare(strict_types=1);

namespace Capell\DemoKit\Actions\Diagnostics;

use Capell\Core\Actions\Diagnostics\VerifyFrontendBuildAssetsAction;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Data\Diagnostics\FrontendBuildAssetVerificationResultData;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Widget;
use Capell\DemoKit\Data\DemoProfileData;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static DemoInstallHealthData run()
 */
final class AssertDefaultDemoInstallHealthAction
{
    use AsObject;

    private DemoProfileData $profile;

    public function handle(): DemoInstallHealthData
    {
        $this->profile = DemoProfileData::default();

        $checks = collect([
            $this->homepageExists(),
            $this->homepageLayoutHasWidgets(),
            $this->homepageStartsWithHero(),
            $this->homepageUsesShowcaseOrder(),
            $this->minimumWidgetCount(),
            $this->apWidgetsHaveAssets(),
            $this->minimumMediaCount(),
            $this->placeholderLabelsAreAbsent(),
            $this->runtimeAssetsExist(),
        ]);

        return new DemoInstallHealthData($checks);
    }

    private function homepageExists(): DoctorCheckResultData
    {
        try {
            $site = Site::query()->default()->with('language')->first() ?? Site::query()->with('language')->first();
            $homepage = $site instanceof Site ? Page::getSiteHomePage($site) : null;
        } catch (Throwable) {
            $homepage = null;
        }

        if (! $homepage instanceof Page) {
            return new DoctorCheckResultData(
                label: 'Default demo homepage exists',
                passed: false,
                message: 'No published homepage was found for the default site.',
                remediation: 'Rerun php artisan capell:install --fresh --demo and confirm the theme demo step completes.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Default demo homepage exists',
            passed: true,
            message: sprintf('Homepage #%d is published.', $homepage->getKey()),
        );
    }

    private function homepageLayoutHasWidgets(): DoctorCheckResultData
    {
        $layout = $this->homepageLayout();
        $widgets = $this->layoutWidgetKeys($layout);

        if ($widgets === []) {
            return new DoctorCheckResultData(
                label: 'Homepage layout has widgets',
                passed: false,
                message: 'The homepage layout does not contain any widget keys.',
                remediation: 'Run the selected theme setup/demo command after package setup has completed.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Homepage layout has widgets',
            passed: true,
            message: sprintf('Homepage layout references %d widget occurrence(s).', count($widgets)),
        );
    }

    private function minimumWidgetCount(): DoctorCheckResultData
    {
        $count = $this->homepageWidgetCount();

        if ($count < $this->profile->minimumWidgetCount) {
            return new DoctorCheckResultData(
                label: 'Default demo widget count',
                passed: false,
                message: sprintf('Homepage has %d widget(s); expected at least %d.', $count, $this->profile->minimumWidgetCount),
                remediation: 'Rerun the demo package step and confirm the demo package runs after setup packages.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Default demo widget count',
            passed: true,
            message: sprintf('Homepage has %d widget(s).', $count),
        );
    }

    private function homepageUsesShowcaseOrder(): DoctorCheckResultData
    {
        $layout = $this->homepageLayout();
        $widgets = $this->layoutWidgetKeys($layout);
        $actual = array_slice($widgets, 0, count($this->profile->showcaseWidgetOrder));

        if ($actual !== $this->profile->showcaseWidgetOrder) {
            return new DoctorCheckResultData(
                label: 'Default demo showcase widget order',
                passed: false,
                message: sprintf(
                    'Homepage starts with [%s]; expected [%s].',
                    implode(', ', $actual),
                    implode(', ', $this->profile->showcaseWidgetOrder),
                ),
                remediation: 'Rerun the demo package step so the curated Foundation showcase homepage layout is rebuilt.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Default demo showcase widget order',
            passed: true,
            message: 'Homepage uses the curated Foundation showcase widget order.',
        );
    }

    private function apWidgetsHaveAssets(): DoctorCheckResultData
    {
        foreach ($this->profile->widgetAssetMinimums as $widgetKey => $minimum) {
            $widget = Widget::query()
                ->where('key', $widgetKey)
                ->withCount('assets')
                ->first();

            $assetCount = $widget instanceof Widget ? (int) $widget->getAttribute('assets_count') : 0;

            if ($assetCount < $minimum) {
                return new DoctorCheckResultData(
                    label: 'Default demo AP widget assets',
                    passed: false,
                    message: sprintf('Widget "%s" has %d asset(s); expected at least %d.', $widgetKey, $assetCount, $minimum),
                    remediation: 'Rerun the default demo fixtures so AP widgets receive their editable content and media assets.',
                );
            }
        }

        return new DoctorCheckResultData(
            label: 'Default demo AP widget assets',
            passed: true,
            message: 'AP showcase widgets have the expected editable assets.',
        );
    }

    private function homepageStartsWithHero(): DoctorCheckResultData
    {
        $firstWidgetKey = $this->firstHomepageWidgetKey();

        if ($firstWidgetKey === null || ! str_contains($firstWidgetKey, 'hero')) {
            return new DoctorCheckResultData(
                label: 'Homepage starts with a hero widget',
                passed: false,
                message: $firstWidgetKey === null
                    ? 'The homepage layout has no first widget.'
                    : sprintf('The homepage starts with "%s", not a hero widget.', $firstWidgetKey),
                remediation: 'Rerun the demo package step after the selected theme setup so the homepage layout order is rebuilt.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Homepage starts with a hero widget',
            passed: true,
            message: sprintf('Homepage starts with "%s".', $firstWidgetKey),
        );
    }

    private function minimumMediaCount(): DoctorCheckResultData
    {
        if (! Schema::hasTable('media')) {
            return new DoctorCheckResultData(
                label: 'Default demo media count',
                passed: false,
                message: 'The media table does not exist.',
                remediation: 'Run php artisan migrate and rerun the demo package step.',
            );
        }

        $count = resolve(ConnectionResolverInterface::class)->table('media')->count();

        if ($count < $this->profile->minimumMediaCount) {
            return new DoctorCheckResultData(
                label: 'Default demo media count',
                passed: false,
                message: sprintf('Demo has %d media record(s); expected at least %d.', $count, $this->profile->minimumMediaCount),
                remediation: 'Rerun php artisan capell:install --fresh --demo and confirm media fixtures publish successfully.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Default demo media count',
            passed: true,
            message: sprintf('Demo has %d media record(s).', $count),
        );
    }

    private function placeholderLabelsAreAbsent(): DoctorCheckResultData
    {
        $homepageWidgetIds = Widget::query()
            ->whereIn('key', $this->layoutWidgetKeys($this->homepageLayout()))
            ->pluck('id');

        $found = Translation::query()
            ->where('translatable_type', resolve(Widget::class)->getMorphClass())
            ->whereIn('translatable_id', $homepageWidgetIds)
            ->where(function ($query): void {
                foreach ($this->profile->placeholderLabels as $label) {
                    $query->orWhere('title', 'like', sprintf('%%%s%%', $label))
                        ->orWhere('content', 'like', sprintf('%%%s%%', $label));
                }
            })
            ->exists();

        if ($found) {
            return new DoctorCheckResultData(
                label: 'Default demo placeholder labels',
                passed: false,
                message: 'The demo still contains placeholder or generic homepage labels.',
                remediation: 'Rerun the default demo fixtures and ensure the Foundation showcase copy replaces generic AP/lorem content.',
            );
        }

        return new DoctorCheckResultData(
            label: 'Default demo placeholder labels',
            passed: true,
            message: 'No known placeholder homepage labels were found.',
        );
    }

    private function runtimeAssetsExist(): DoctorCheckResultData
    {
        $failures = VerifyFrontendBuildAssetsAction::run()
            ->reject(fn (FrontendBuildAssetVerificationResultData $result): bool => $result->passed);

        if ($failures->isNotEmpty()) {
            $firstFailure = $failures->first();

            return new DoctorCheckResultData(
                label: 'Required published runtime assets',
                passed: false,
                message: $firstFailure->message,
                remediation: $firstFailure->remediation,
            );
        }

        return new DoctorCheckResultData(
            label: 'Required published runtime assets',
            passed: true,
            message: 'All registered runtime build assets are published.',
        );
    }

    private function homepageWidgetCount(): int
    {
        $layout = $this->homepageLayout();
        if (! $layout instanceof Layout) {
            return 0;
        }

        return count(array_unique($this->layoutWidgetKeys($layout)));
    }

    private function homepageLayout(): ?Layout
    {
        try {
            $site = Site::query()->default()->with('language')->first() ?? Site::query()->with('language')->first();
            $homepage = $site instanceof Site ? Page::getSiteHomePage($site) : null;

            return $homepage?->layout;
        } catch (Throwable) {
            return null;
        }
    }

    private function firstHomepageWidgetKey(): ?string
    {
        $layout = $this->homepageLayout();
        if (! $layout instanceof Layout) {
            return null;
        }

        foreach ($layout->containers ?? [] as $container) {
            if (! is_array($container)) {
                continue;
            }

            $widget = collect($container['widgets'] ?? [])->first();

            if (! is_array($widget)) {
                continue;
            }

            $key = (string) ($widget['widget_key'] ?? $widget['key'] ?? '');

            return $key !== '' ? $key : null;
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function layoutWidgetKeys(?Layout $layout): array
    {
        if (! $layout instanceof Layout) {
            return [];
        }

        $containerWidgetKeys = collect($layout->containers ?? [])
            ->flatMap(function (mixed $container): array {
                if (! is_array($container)) {
                    return [];
                }

                $widgets = $container['widgets'] ?? [];

                return is_array($widgets) ? $widgets : [];
            })
            ->map(fn (mixed $widget): ?string => is_array($widget)
                ? (string) ($widget['widget_key'] ?? $widget['key'] ?? '')
                : (is_string($widget) ? $widget : null))
            ->filter(fn (?string $key): bool => $key !== null && $key !== '')
            ->values();

        if ($containerWidgetKeys->isNotEmpty()) {
            return $containerWidgetKeys->all();
        }

        return collect($layout->widgets ?? [])
            ->flatMap(fn (mixed $container): Collection => is_array($container) && array_key_exists('widgets', $container)
                ? collect($container['widgets'] ?? [])
                : collect([$container]))
            ->map(fn (mixed $widget): ?string => is_array($widget)
                ? (string) ($widget['widget_key'] ?? $widget['key'] ?? '')
                : (is_string($widget) ? $widget : null))
            ->filter(fn (?string $key): bool => $key !== null && $key !== '')
            ->values()
            ->all();
    }
}
