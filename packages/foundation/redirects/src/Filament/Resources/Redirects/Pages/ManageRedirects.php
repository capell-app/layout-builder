<?php

declare(strict_types=1);

namespace Capell\Redirects\Filament\Resources\Redirects\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Actions\CreateAction;
use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Redirects\Filament\Exports\RedirectExporter;
use Capell\Redirects\Filament\Imports\RedirectImporter;
use Capell\Redirects\Filament\Resources\Redirects\RedirectResource;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Override;

class ManageRedirects extends ManageRecords
{
    /** @return class-string<RedirectResource> */
    #[Override]
    public static function getResource(): string
    {
        return CapellAdmin::getResource('Redirect');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('redirects::hints.redirects');
    }

    #[Override]
    public function mount(): void
    {
        parent::mount();

        if (! request()->boolean('create_redirect')) {
            return;
        }

        $siteId = request()->integer('site_id');
        $languageId = request()->integer('language_id');
        $url = request()->string('url')->toString();
        $targetUrl = request()->string('target_url')->toString();

        $createRedirectData = [
            'site_id' => $siteId !== 0 ? $siteId : null,
            'language_id' => $languageId !== 0 ? $languageId : null,
            'url' => $url !== '' ? $url : null,
            'target_url' => $targetUrl !== '' ? $targetUrl : null,
            'status_code' => request()->integer('status_code', RedirectStatusCodeEnum::Permanent->value),
        ];

        $this->mountAction('create', $createRedirectData);

        $mountedActionIndex = array_key_last($this->mountedActions);

        if ($mountedActionIndex === null) {
            return;
        }

        $mountedActionData = $this->mountedActions[$mountedActionIndex]['data'] ?? [];

        if (! is_array($mountedActionData)) {
            $mountedActionData = [];
        }

        $this->mountedActions[$mountedActionIndex]['data'] = [
            ...$mountedActionData,
            ...array_filter(
                $createRedirectData,
                static fn (mixed $value): bool => $value !== null,
            ),
        ];
    }

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
            ImportAction::make()
                ->authorize(fn (): bool => Gate::allows('import', RedirectResource::getModel()))
                ->importer(RedirectImporter::class),
            ExportAction::make()
                ->authorize(fn (): bool => Gate::allows('export', RedirectResource::getModel()))
                ->exporter(RedirectExporter::class),
        ];
    }
}
