<?php

declare(strict_types=1);

namespace Capell\ContentSections\Actions;

use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\Core\Models\Type;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Type|null run(array $state = [])
 */
class ResolveRequestedSectionTypeAction
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

        $sectionKey = request()->query('section');

        if (! is_string($sectionKey) || $sectionKey === '') {
            return null;
        }

        return EnsureSectionTypeForKeyAction::run($sectionKey);
    }

    public function defaultType(): Type
    {
        EnsureSectionTypeForKeyAction::run('content');

        /** @var Type $type */
        $type = Type::query()
            ->where('type', LayoutTypeEnum::Section->value)
            ->orderBy('default', 'desc')
            ->orderBy('id')
            ->firstOrFail();

        return $type;
    }
}
