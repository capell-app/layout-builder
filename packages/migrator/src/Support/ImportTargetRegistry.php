<?php

declare(strict_types=1);

namespace Capell\Migrator\Support;

use Capell\Core\Models\Page;
use Capell\Core\Models\Type;

final class ImportTargetRegistry
{
    /** @var array<string, class-string> */
    private array $targets = [
        'page' => Page::class,
        'type' => Type::class,
        'collection' => Page::class,
    ];

    /**
     * @param  class-string  $modelClass
     */
    public function register(string $key, string $modelClass): void
    {
        $this->targets[$key] = $modelClass;
    }

    /**
     * @return array<string, class-string>
     */
    public function all(): array
    {
        return $this->targets;
    }
}
