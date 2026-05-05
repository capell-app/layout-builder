<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Actions;

use Capell\ContentBlocks\Enums\LayoutTypeEnum;
use Capell\Core\Models\Type;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Type|null run(array $state = [])
 */
class ResolveRequestedContentBlockTypeAction
{
    use AsObject;

    public function handle(array $state = []): ?Type
    {
        $typeId = $state['type_id'] ?? null;

        if ($typeId !== null && $typeId !== '') {
            /** @var Type|null $type */
            $type = Type::query()->find($typeId);

            return $type;
        }

        $blockKey = request()->query('block');

        if (! is_string($blockKey) || $blockKey === '') {
            return null;
        }

        return EnsureContentBlockTypeForKeyAction::run($blockKey);
    }

    public function defaultType(): Type
    {
        /** @var Type $type */
        $type = Type::query()
            ->where('type', LayoutTypeEnum::ContentBlock->value)
            ->orderBy('default', 'desc')
            ->orderBy('id')
            ->firstOrFail();

        return $type;
    }
}
