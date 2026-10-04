<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Feature;

use Capell\ContentSections\Providers\ContentSectionsServiceProvider;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Exceptions\UnknownBlueprintSubjectException;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Blueprint;
use Capell\Core\Support\BlueprintSubjectRegistry;
use Capell\LayoutBuilder\Actions\SetupLayoutBuilderPackageAction;
use Capell\LayoutBuilder\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Creator\TypeCreator;
use Capell\LayoutBuilder\Tests\LayoutBuilderTestCase;
use Override;

final class LayoutBuilderSetupWithoutContentSectionsTest extends LayoutBuilderTestCase
{
    public function test_setup_preserves_layout_and_widget_defaults_without_content_sections(): void
    {
        expect(app()->getProvider(ContentSectionsServiceProvider::class))->toBeNull()
            ->and(CapellCore::isPackageEnabled('capell-app/content-sections'))->toBeFalse()
            ->and(resolve(BlueprintSubjectRegistry::class)->has('section'))->toBeFalse();

        $package = CapellCore::getPackage('capell-app/layout-builder');
        SetupLayoutBuilderPackageAction::run($package);
        SetupLayoutBuilderPackageAction::run($package);

        expect(Layout::query()->where('key', LayoutEnum::Default->value)->exists())->toBeTrue()
            ->and(Widget::query()->where('key', 'page-content')->exists())->toBeTrue()
            ->and(Blueprint::query()->where('type', 'widget')->where('key', 'hero')->count())->toBe(1)
            ->and(Blueprint::query()->where('type', 'section')->exists())->toBeFalse()
            ->and(CapellCore::hasPageType('section'))->toBeFalse()
            ->and(resolve(BlueprintSubjectRegistry::class)->has('section'))->toBeFalse();
    }

    public function test_enabled_content_sections_requires_its_own_registered_subject(): void
    {
        CapellCore::markPackageInstalled('capell-app/content-sections');

        expect(CapellCore::isPackageEnabled('capell-app/content-sections'))->toBeTrue()
            ->and(resolve(BlueprintSubjectRegistry::class)->has('section'))->toBeFalse();

        $creator = resolve(TypeCreator::class);

        expect(fn () => $creator->createDefaultContentType())->toThrow(UnknownBlueprintSubjectException::class)
            ->and(fn () => $creator->createBuilderContentType())->toThrow(UnknownBlueprintSubjectException::class)
            ->and(CapellCore::hasPageType('section'))->toBeFalse();
    }

    /** @return class-string[] */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return array_values(array_filter(
            parent::getPackageProviders($app),
            fn (string $provider): bool => $provider !== ContentSectionsServiceProvider::class,
        ));
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::markPackageUninstalled('capell-app/content-sections');
    }
}
