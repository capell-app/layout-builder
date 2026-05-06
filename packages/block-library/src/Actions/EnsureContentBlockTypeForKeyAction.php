<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Actions;

use BackedEnum;
use Capell\BlockLibrary\Data\ContentBlockDefinitionData;
use Capell\BlockLibrary\Enums\LayoutTypeEnum;
use Capell\BlockLibrary\Support\ContentBlockRegistry;
use Capell\Core\Models\Type;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static Type run(string $key)
 */
class EnsureContentBlockTypeForKeyAction
{
    use AsObject;

    public function handle(string $key): Type
    {
        $registry = resolve(ContentBlockRegistry::class);

        if ($registry->all() === []) {
            RegisterDefaultBlockLibraryAction::run($registry);
        }

        $definition = $registry->get($key);

        if (! $definition instanceof ContentBlockDefinitionData) {
            throw new InvalidArgumentException(sprintf('Content block [%s] is not registered.', $key));
        }

        $configuratorKey = $definition->configurator::getKey();

        /** @var Type|null $type */
        $type = Type::query()
            ->where('type', LayoutTypeEnum::ContentBlock->value)
            ->where('key', $definition->key)
            ->first();

        if ($type instanceof Type) {
            return $type;
        }

        /** @var Type $type */
        $type = Type::query()->create([
            'name' => $definition->label,
            'key' => $definition->key,
            'type' => LayoutTypeEnum::ContentBlock->value,
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
