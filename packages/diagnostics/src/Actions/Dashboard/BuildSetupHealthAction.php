<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Actions\Dashboard;

use Capell\Admin\Enums\SetupHealthEnum;
use Capell\Admin\Filament\Resources\Languages\LanguageResource;
use Capell\Admin\Filament\Resources\Sites\SiteResource;
use Capell\Admin\Filament\Resources\Themes\ThemeResource;
use Capell\Admin\Filament\Resources\Types\TypeResource;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Type;
use Capell\Diagnostics\Data\Dashboard\SetupCheckData;
use Capell\Diagnostics\Data\Dashboard\SetupHealthData;
use Exception;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\LaravelData\DataCollection;

final class BuildSetupHealthAction
{
    use AsAction;

    public function handle(): SetupHealthData
    {
        $siteExists = Site::query()->exists();
        $languageExists = Language::query()->exists();
        $themeExists = Theme::query()->exists();
        $typeExists = Type::query()->exists();

        $checks = [
            new SetupCheckData(
                id: 'site',
                label: __('capell-admin::setup-health.site.label'),
                status: $siteExists ? SetupHealthEnum::Green : SetupHealthEnum::Red,
                fixUrl: $siteExists ? null : $this->tryGetUrl(SiteResource::class, 'create'),
                fixLabel: $siteExists ? null : __('capell-admin::setup-health.site.fix_label'),
            ),
            new SetupCheckData(
                id: 'language',
                label: __('capell-admin::setup-health.language.label'),
                status: $languageExists ? SetupHealthEnum::Green : SetupHealthEnum::Red,
                fixUrl: $languageExists ? null : $this->tryGetUrl(LanguageResource::class, 'create'),
                fixLabel: $languageExists ? null : __('capell-admin::setup-health.language.fix_label'),
            ),
            new SetupCheckData(
                id: 'theme',
                label: __('capell-admin::setup-health.theme.label'),
                status: $themeExists ? SetupHealthEnum::Green : SetupHealthEnum::Amber,
                fixUrl: $themeExists ? null : $this->tryGetUrl(ThemeResource::class, 'create'),
                fixLabel: $themeExists ? null : __('capell-admin::setup-health.theme.fix_label'),
            ),
            new SetupCheckData(
                id: 'type',
                label: __('capell-admin::setup-health.type.label'),
                status: $typeExists ? SetupHealthEnum::Green : SetupHealthEnum::Red,
                fixUrl: $typeExists ? null : $this->tryGetUrl(TypeResource::class, 'create'),
                fixLabel: $typeExists ? null : __('capell-admin::setup-health.type.fix_label'),
            ),
        ];

        $allGreen = collect($checks)->every(fn (SetupCheckData $check): bool => $check->status === SetupHealthEnum::Green);

        return new SetupHealthData(
            checks: SetupCheckData::collect($checks, DataCollection::class),
            allGreen: $allGreen,
        );
    }

    /** @param class-string $resource */
    private function tryGetUrl(string $resource, string $name): ?string
    {
        try {
            return $resource::getUrl($name);
        } catch (Exception) {
            return null;
        }
    }
}
