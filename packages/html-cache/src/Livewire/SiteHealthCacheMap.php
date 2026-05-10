<?php

declare(strict_types=1);

namespace Capell\HtmlCache\Livewire;

use Capell\Admin\Support\SiteScope;
use Capell\HtmlCache\Actions\BuildCacheMapOverviewAction;
use Capell\HtmlCache\Actions\ListCacheMapResourceOptionsAction;
use Capell\HtmlCache\Data\CacheMap\CacheMapOverviewData;
use Capell\HtmlCache\Data\CacheMap\CacheMapResourceSummaryData;
use Capell\HtmlCache\Filament\Resources\CachedModelUrls\Tables\CachedModelUrlsTable;
use Capell\HtmlCache\Models\CachedModelUrl;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class SiteHealthCacheMap extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?int $siteId = null;

    public ?string $selectedModelType = null;

    public ?string $selectedResourceKey = null;

    public string $resourceSearch = '';

    /** @var list<string> */
    public array $clearedCacheMapRecordKeys = [];

    public function mount(?int $siteId = null): void
    {
        $this->siteId = $siteId;
    }

    public function table(Table $table): Table
    {
        return CachedModelUrlsTable::configure($table, $this->query(), isSiteScoped: $this->siteId !== null, showFilters: false);
    }

    public function render(): View
    {
        return view('capell-html-cache::livewire.site-health-cache-map');
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    /**
     * @return Model|array<string, mixed>|null
     */
    public function getTableRecord(?string $key): Model|array|null
    {
        if ($key === null) {
            return null;
        }

        /** @var Builder<CachedModelUrl> $query */
        $query = CachedModelUrl::query();

        if ($this->siteId !== null) {
            $query->where('site_id', $this->siteId);
        }

        $record = $query->find($key);

        if ($record instanceof CachedModelUrl) {
            return $record;
        }

        return (new CachedModelUrl)->forceFill([
            'id' => (int) $key,
            'url' => '',
            'url_hash' => '',
            'path' => '/',
            'cacheable_type' => CachedModelUrl::class,
            'cacheable_id' => 0,
        ]);
    }

    public function rememberClearedCacheMapRecordKey(string $key): void
    {
        $this->clearedCacheMapRecordKeys[] = $key;
    }

    public function updatedSelectedModelType(): void
    {
        $this->selectedResourceKey = null;
        $this->resourceSearch = '';
        $this->resetTable();
    }

    public function updatedSelectedResourceKey(): void
    {
        $this->resetTable();
    }

    public function updatedResourceSearch(): void
    {
        $this->selectedResourceKey = null;
    }

    public function selectModel(string $modelType): void
    {
        $this->selectedModelType = $modelType;
        $this->selectedResourceKey = null;
        $this->resourceSearch = '';
        $this->resetTable();
    }

    public function selectResource(string $modelType, string $resourceKey): void
    {
        $this->selectedModelType = $modelType;
        $this->selectedResourceKey = $resourceKey;
        $this->resetTable();
    }

    public function openDetail(): void
    {
        $this->dispatch('open-modal', id: 'html-cache-map-detail');
    }

    public function openResourceDetail(string $modelType, string $resourceKey): void
    {
        $this->selectResource($modelType, $resourceKey);
        $this->openDetail();
    }

    public function clearSelection(): void
    {
        $this->selectedModelType = null;
        $this->selectedResourceKey = null;
        $this->resourceSearch = '';
        $this->resetTable();
    }

    #[Computed]
    public function overview(): CacheMapOverviewData
    {
        return BuildCacheMapOverviewAction::run($this->siteId);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function modelOptions(): array
    {
        $options = [];

        foreach ($this->overview->modelSummaries as $summary) {
            $options[$summary->modelType] = $summary->label;
        }

        return $options;
    }

    /**
     * @return list<CacheMapResourceSummaryData>
     */
    #[Computed]
    public function resourceOptions(): array
    {
        if (blank($this->selectedModelType)) {
            return [];
        }

        return ListCacheMapResourceOptionsAction::run($this->selectedModelType, $this->siteId, $this->resourceSearch);
    }

    public function selectedResource(): ?CacheMapResourceSummaryData
    {
        foreach ($this->resourceOptions as $resource) {
            if ($resource->key === $this->selectedResourceKey) {
                return $resource;
            }
        }

        $resource = $this->decodedSelectedResource();

        if ($resource === null) {
            return null;
        }

        return new CacheMapResourceSummaryData(
            key: (string) $this->selectedResourceKey,
            modelType: $resource['modelType'],
            modelLabel: class_basename($resource['modelType']),
            resourceId: $resource['resourceId'],
            label: class_basename($resource['modelType']) . ' #' . $resource['resourceId'],
            dependencyCount: 0,
            urlCount: 0,
        );
    }

    public function detailUrlCount(): int
    {
        return $this->query()->distinct('url_hash')->count('url_hash');
    }

    /**
     * @return Builder<CachedModelUrl>
     */
    private function query(): Builder
    {
        /** @var Builder<CachedModelUrl> $query */
        $query = SiteScope::applyForCurrentActor(CachedModelUrl::query(), denyWhenMissingActor: true);

        if ($this->siteId !== null) {
            $query->where('site_id', $this->siteId);
        }

        if (filled($this->selectedModelType)) {
            $query->where('cacheable_type', $this->selectedModelType);
        }

        $selectedResource = $this->decodedSelectedResource();

        if ($selectedResource !== null) {
            $query
                ->where('cacheable_type', $selectedResource['modelType'])
                ->where('cacheable_id', $selectedResource['resourceId']);
        }

        return $query;
    }

    /**
     * @return array{modelType: string, resourceId: int}|null
     */
    private function decodedSelectedResource(): ?array
    {
        if (blank($this->selectedResourceKey)) {
            return null;
        }

        $decoded = base64_decode($this->selectedResourceKey, true);

        if (! is_string($decoded) || ! str_contains($decoded, '|')) {
            return null;
        }

        [$modelType, $resourceId] = explode('|', $decoded, 2);

        if ($modelType === '' || ! ctype_digit($resourceId)) {
            return null;
        }

        return [
            'modelType' => $modelType,
            'resourceId' => (int) $resourceId,
        ];
    }
}
