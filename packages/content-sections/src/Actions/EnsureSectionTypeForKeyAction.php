<?php

declare(strict_types=1);

namespace Capell\ContentSections\Actions;

use BackedEnum;
use Capell\ContentSections\Data\SectionDefinitionData;
use Capell\ContentSections\Enums\LayoutTypeEnum;
use Capell\ContentSections\Support\SectionRegistry;
use Capell\Core\Models\Type;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Type run(string $key)
 */
class EnsureSectionTypeForKeyAction
{
    use AsObject;

    public function handle(string $key): Type
    {
        $registry = resolve(SectionRegistry::class);

        if ($registry->all() === []) {
            RegisterDefaultSectionsAction::run($registry);
        }

        $definition = $registry->get($key);

        if (! $definition instanceof SectionDefinitionData) {
            throw new InvalidArgumentException(sprintf('section [%s] is not registered.', $key));
        }

        $configuratorKey = $definition->configurator::getKey();

        /** @var Type|null $type */
        $type = Type::query()
            ->where('type', LayoutTypeEnum::Section->value)
            ->where('key', $definition->key)
            ->first();

        if ($type instanceof Type) {
            return $type;
        }

        /** @var Type $type */
        $type = Type::query()->create([
            'name' => $definition->label,
            'key' => $definition->key,
            'type' => LayoutTypeEnum::Section->value,
            'group' => $definition->group,
            'default' => $definition->key === 'content',
            'status' => true,
            'admin' => [
                'configurator' => $configuratorKey,
                'icon' => $definition->icon instanceof BackedEnum ? $definition->icon->value : $definition->icon,
            ],
        ]);

        return $type;
    }
}
