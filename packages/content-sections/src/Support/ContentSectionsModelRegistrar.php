<?php

declare(strict_types=1);

namespace Capell\ContentSections\Support;

use Capell\ContentSections\Models\Section;
use Capell\Core\Facades\CapellCore;
use Illuminate\Database\Eloquent\Relations\Relation;

class ContentSectionsModelRegistrar
{
    public static function register(): void
    {
        CapellCore::registerModels([Section::class]);

        Relation::morphMap([
            'section' => Section::class,
        ], merge: true);
    }
}
