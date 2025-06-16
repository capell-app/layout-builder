<?php

declare(strict_types=1);

namespace Capell\Blog\Database\Factories;

use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Models\Page;
use Capell\Core\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class ArticlePageFactory extends PageFactory
{
    public function article(?Page $parent = null): self
    {
        return $this->state(fn (): array => [
            'type_id' => Type::pageType()->firstWhere('key', 'article') ?? Type::factory()->state(['key' => 'article']),
            'parent_uuid' => $parent?->getUuid(),
        ]);
    }
}
