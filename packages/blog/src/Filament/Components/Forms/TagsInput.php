<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Components\Forms;

use Capell\Blog\Enums\ModelEnum;
use Capell\Blog\Models\Tag;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

abstract class TagsInput extends SpatieTagsInput
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('capell-blog::form.tags'))
            ->suggestions(function (self $component, Get $get): array {
                /** @var class-string<Tag> $model */
                $model = CapellCore::getModel(ModelEnum::Tag);

                return $model::query()->where('type', $component->type)
                    ->where(
                        fn (Builder $query) => $query->whereNull('site_id')
                            ->orWhere('site_id', $get('site_id')),
                    )
                    ->pluck('name')
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
}
