<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Widgets;

use Capell\Admin\Actions\DeletePageCacheAction;
use Capell\Admin\Data\MessageData;
use Capell\Admin\Enums\AlertTypeEnum;
use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Admin\Filament\Resources\Sites\SiteResource;
use Capell\Admin\Filament\Widgets\ResourceAlertsWidget;
use Capell\Core\Actions\GetResourceFromTypeAction;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Models\Page;
use Capell\Core\Support\Cache\PageCacheService;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use RuntimeException;

class PageAlertsWidget extends ResourceAlertsWidget
{
    public ?Pageable $record = null;

    public function mount(): void
    {
        $this->loadRecord();
    }

    public function hydrate(): void
    {
        $this->loadRecord();
    }

    public function clearCacheAction(): Action
    {
        return Action::make('clearCache')
            ->label(__('capell-admin::button.clear_cache'))
            ->icon('heroicon-c-trash')
            ->color('warning')
            ->link()
            ->size(Size::Small)
            ->action(function (): void {
                DeletePageCacheAction::dispatch($this->record);

                Notification::make()
                    ->title(__('capell-admin::notification.page_cache_cleared'))
                    ->success()
                    ->send();
            });
    }

    public function viewSiteAction(): Action
    {
        return Action::make('viewSite')
            ->label(__('capell-admin::button.edit_site'))
            ->link()
            ->url(SiteResource::getUrl('edit', ['record' => $this->record->site->id]));
    }

    public function viewCanonicalsAction(): Action
    {
        return Action::make('viewCanonicals')
            ->label(__('capell-admin::button.view_pages'))
            ->visible(fn (): bool => (bool) $this->record->canonical_pages_count)
            ->url(
                self::getResource()::getUrl(
                    'index',
                    ['filters[filter][canonical_page_id]' => $this->record->getKey()],
                ),
            );
    }

    protected static function getCachedPage(Pageable $page): ?string
    {
        foreach ($page->pageUrls as $url) {
            $url->loadMissing('siteDomain');

            if ($url->siteDomain === null) {
                continue;
            }

            if (isset($url->page_cache_file) && $url->page_cache_file !== '') {
                return $url->page_cache_file;
            }
        }

        return null;
    }

    /**
     * @return Collection<string, MessageData>
     */
    protected function buildAlerts(): Collection
    {
        $alerts = collect();

        $pageStatus = $this->draftStatusAlert();

        if ($pageStatus instanceof MessageData) {
            $alerts->put('pageStatus', $pageStatus);
        }

        $pageCache = resolve(PageCacheService::class);

        if ($this->record->trashed()) {
            $alerts->put('deleted', new MessageData(
                message: __('capell-admin::message.resource_deleted'),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-m-exclamation-triangle',
            ));
        }

        if ($this->record->site->trashed()) {
            $alerts->put('deleted_site', new MessageData(
                message: __('capell-admin::message.page_site_deleted'),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-m-exclamation-triangle',
                action: $this->viewSiteAction(),
            ));
        }

        $this->record->loadCount('pageUrls');

        if ($this->record->page_urls_count === 0) {
            $alerts->put('missingUrl', new MessageData(
                message: __('capell-admin::message.page_no_urls'),
                type: AlertTypeEnum::Warning,
                icon: 'heroicon-o-link',
            ));
        }

        $this->record->loadCount('canonicalPages');

        if (($this->record->canonical_pages_count ?? 0) > 0) {
            $alerts->put('referenced', new MessageData(
                message: __('capell-admin::message.canonical_page_count', [
                    'count' => $this->record->canonical_pages_count,
                ]),
                type: AlertTypeEnum::Info,
                icon: 'heroicon-o-information-circle',
                action: $this->viewCanonicalsAction(),
            ));
        }

        switch ($this->record->publish_status) {
            case PublishStatusEnum::pending:
                $alerts->put('pending', new MessageData(
                    message: __('capell-admin::message.resource_pending', [
                        'date' => $this->record->visible_from?->diffForHumans(),
                        'name' => __('capell-admin::generic.page'),
                    ]),
                    type: AlertTypeEnum::Warning,
                    icon: 'heroicon-o-clock',
                ));
                break;
            case PublishStatusEnum::expired:
                $alerts->put('expired', new MessageData(
                    message: __('capell-admin::message.resource_expired', [
                        'date' => $this->record->visible_until?->diffForHumans(),
                        'name' => strtolower(__('capell-admin::generic.page')),
                    ]),
                    type: AlertTypeEnum::Warning,
                    icon: 'heroicon-o-clock',
                ));
                break;
        }

        $cachedPage = static::getCachedPage($this->record);
        if ($cachedPage !== null && $pageCache->exists($cachedPage)) {
            $lastModified = $pageCache->lastModified($cachedPage);
            $time = $lastModified !== null ? Date::createFromTimestamp($lastModified) : null;

            $alerts->put('cached', new MessageData(
                message: __(
                    'capell-admin::message.page_cached_warning',
                    ['diff_time' => $time?->diffForHumans()],
                ),
                type: AlertTypeEnum::Info,
                icon: 'heroicon-o-check-badge',
                action: $this->clearCacheAction(),
            ));
        }

        return $alerts;
    }

    protected function loadRecord(): void
    {
        throw_unless($this->record instanceof Pageable, RuntimeException::class, 'Record must be an instance of ' . Page::class);

        $this->record->load([
            'site' => fn (BuilderContract $query): BuilderContract => $query->withTrashed(),
            'type',
            'pageUrls',
        ]);
    }

    private function draftStatusAlert(): ?MessageData
    {
        $record = $this->record;

        $workspaceId = $record->getAttribute('workspace_id');

        if ($workspaceId === null || (int) $workspaceId === 0) {
            return null;
        }

        $workspace = $record->workspace;

        $actions = [];

        $previewUrl = rescue(fn (): ?string => $record->pageUrls->first()?->full_url, null, false);

        if ($previewUrl !== null && $previewUrl !== '') {
            $actions[] = Action::make('previewDraft')
                ->label(__('capell-admin::button.preview'))
                ->link()
                ->size(Size::Small)
                ->icon('heroicon-o-eye')
                ->url($previewUrl)
                ->openUrlInNewTab();
        }

        $actions[] = Action::make('openWorkspace')
            ->label(__('capell-admin::message.page_status_open_workspace'))
            ->link()
            ->size(Size::Small)
            ->url(WorkspaceResource::getUrl('index'));

        return new MessageData(
            message: __('capell-admin::message.page_status_draft', [
                'workspace' => $workspace?->name ?? '—',
            ]),
            type: AlertTypeEnum::Info,
            icon: 'heroicon-o-document-text',
            action: $actions,
        );
    }

    /**
     * @return class-string<PageResource>
     */
    private function getResource(): string
    {
        return GetResourceFromTypeAction::run(ResourceEnum::Page, $this->record->type) ?? PageResource::class;
    }
}
