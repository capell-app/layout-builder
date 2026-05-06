<?php

declare(strict_types=1);

namespace Capell\Tags\Filament\Components\Forms;

use Capell\Admin\Support\SiteScope;
use Capell\Core\Models\Language;
use Capell\Tags\Models\Tag;
use Filament\FormBuilder\Components\SpatieTagsInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Override;

abstract class TagsInput extends SpatieTagsInput
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-tags::form.tags'))
            ->suggestions(function (self $component, Get $get): array {
                /** @var class-string<Tag> $model */
                $model = Tag::class;

                return $model::query()->where('type', $component->type)
                    ->where(function (Builder $query) use ($get): void {
                        $query->whereNull('site_id');

                        $siteIds = $this->accessibleSuggestionSiteIds($get('site_id'));

                        if ($siteIds->isNotEmpty()) {
                            $query->orWhereIn('site_id', $siteIds);
                        }
                    })
                    ->get()
                    ->map(fn (Tag $tag): string => (string) $tag->name)
                    ->filter(fn (string $name): bool => $name !== '')
                    ->values()
                    ->all();
            })
            ->loadStateFromRelationshipsUsing(static function (SpatieTagsInput $component, ?Model $record): void {
                if (! method_exists($record, 'tagsWithType')) {
                    return;
                }

                $type = $component->getType();

                $record->load('tags');

                $tags = $component->isAnyTagTypeAllowed() ? $record->getRelationValue('tags') : $record->tagsWithType($type);

                $language = $record->getPrimaryLanguage();

                $locale = $language instanceof Language ? $language->code : app()->getLocale();

                $component->state(
                    $tags->map(fn (Tag $tag): ?string => $tag->getTranslation('name', $locale))->all(),
                );
            })
            ->saveRelationshipsUsing(static function (SpatieTagsInput $component, ?Model $record, array $state): void {
                if (! (method_exists($record, 'syncTagsWithType') && method_exists($record, 'syncTags'))) {
                    return;
                }

                if (
                    ($type = $component->getType()) &&
                    (! $component->isAnyTagTypeAllowed())
                ) {
                    $record->syncTagsWithType($state, $type);

                    return;
                }

                $component->syncTagsWithAnyType($record, $state);
            });
    }

    /**
     * @return Collection<int, int>
     */
    private function accessibleSuggestionSiteIds(mixed $selectedSiteId): Collection
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable) {
            return collect();
        }

        if (SiteScope::isGlobalActor($actor)) {
            return is_numeric($selectedSiteId) ? collect([(int) $selectedSiteId]) : collect();
        }

        if (! method_exists($actor, 'getAssignedSiteIds')) {
            return collect();
        }

        $assignedSiteIds = $actor->getAssignedSiteIds()
            ->map(fn (mixed $siteId): int => (int) $siteId)
            ->values();

        if (! is_numeric($selectedSiteId)) {
            return $assignedSiteIds;
        }

        $selectedSiteId = (int) $selectedSiteId;

        return $assignedSiteIds->contains($selectedSiteId)
            ? collect([$selectedSiteId])
            : collect();
    }
}
