<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Models\Blueprint;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolvePageContentStructureAction
{
    use AsFake;
    use AsObject;

    public function handle(?Pageable $page): ?ContentStructure
    {
        if (! $page instanceof Model) {
            return null;
        }

        $override = $page->getAttributes()['content_structure_override'] ?? null;
        $structure = is_string($override) ? ContentStructure::tryFrom($override) : null;
        if ($structure instanceof ContentStructure) {
            return $structure;
        }

        // Public rendering must never load a missing blueprint relationship.
        $blueprint = $page->relationLoaded('blueprint') ? $page->getRelation('blueprint') : null;

        return $blueprint instanceof Blueprint ? $blueprint->content_structure : null;
    }
}
