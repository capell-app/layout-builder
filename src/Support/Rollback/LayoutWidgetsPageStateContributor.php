<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Rollback;

use Capell\Core\EventSourcing\Contracts\PageStateContributor;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * Adds a page's Layout Builder placements (its `widget_assets` rows) to page
 * revisions, so rolling a page back restores its layout with its content.
 *
 * Rows are captured raw and restored with their original ids. Restore runs
 * event-silently, so deleting a placement leaves its media rows in place and
 * restoring the placement with the same id reattaches them.
 */
final class LayoutWidgetsPageStateContributor implements PageStateContributor
{
    /**
     * @var list<string>
     */
    private const array COLUMNS = [
        'id',
        'workspace_id',
        'widget_id',
        'container',
        'occurrence',
        'asset_type',
        'asset_id',
        'order',
        'meta',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    /**
     * @var list<string>|null
     */
    private ?array $columns = null;

    #[Override]
    public function key(): string
    {
        return 'layoutWidgets';
    }

    #[Override]
    public function capture(Page $page): array
    {
        return $this->placements($page)
            ->orderBy('id')
            ->get($this->columns())
            ->map(static fn (object $row): array => (array) $row)
            ->values()
            ->all();
    }

    #[Override]
    public function restore(Page $page, array $state): void
    {
        $table = new WidgetAsset()->getTable();
        $columns = $this->columns();

        $this->placements($page)->delete();

        foreach ($state as $row) {
            if (! is_array($row)) {
                continue;
            }

            $attributes = array_intersect_key($row, array_flip($columns));
            $attributes['pageable_type'] = $page->getMorphClass();
            $attributes['pageable_id'] = $page->getKey();

            if (isset($attributes['id']) && DB::table($table)->where('id', $attributes['id'])->exists()) {
                unset($attributes['id']);
            }

            DB::table($table)->insert($attributes);
        }
    }

    /**
     * Placements on this page in the page's own workspace, matching the rows
     * the page's `widgetAssets` relation and the publisher treat as its layout.
     */
    private function placements(Page $page): Builder
    {
        $query = DB::table(new WidgetAsset()->getTable())
            ->where('pageable_type', $page->getMorphClass())
            ->where('pageable_id', $page->getKey());

        if (in_array('workspace_id', $this->columns(), true)) {
            $workspaceId = $page->getAttributes()['workspace_id'] ?? 0;

            $query->where('workspace_id', is_numeric($workspaceId) ? (int) $workspaceId : 0);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    private function columns(): array
    {
        return $this->columns ??= array_values(array_intersect(
            self::COLUMNS,
            DB::getSchemaBuilder()->getColumnListing(new WidgetAsset()->getTable()),
        ));
    }
}
