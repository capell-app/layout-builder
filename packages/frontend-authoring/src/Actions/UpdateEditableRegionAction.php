<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Actions;

use Capell\FrontendAuthoring\Data\EditableRegionPayloadData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsObject;

class UpdateEditableRegionAction
{
    use AsObject;

    /**
     * @return array{cleared: int, urls: list<string>}
     */
    public function handle(EditableRegionPayloadData $payload, string $value): array
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $payload->model;
        abort_unless(is_subclass_of($modelClass, Model::class), 403);

        $record = $modelClass::query()->findOrFail($payload->recordKey);
        $urls = CollectAffectedCachedUrlsAction::run($record);

        $this->applyValue($record, $payload->field, $value);
        $record->save();

        $cleared = ClearAffectedCachedUrlsAction::run($record, $urls, $payload->currentUrl);

        return [
            'cleared' => $cleared,
            'urls' => $urls,
        ];
    }

    private function applyValue(Model $record, string $field, string $value): void
    {
        if ($field === 'title' || $field === 'content') {
            $record->setAttribute($field, $value);

            return;
        }

        if (str_starts_with($field, 'meta.')) {
            $meta = (array) $record->getAttribute('meta');
            Arr::set($meta, substr($field, 5), $value);
            $record->setAttribute('meta', $meta);

            return;
        }

        abort(403);
    }
}
