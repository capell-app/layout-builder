<?php

declare(strict_types=1);

namespace Capell\Media\Extenders;

use Capell\Core\Contracts\ModelMediaExtender as ModelMediaExtenderContract;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;

class ModelMediaExtender implements ModelMediaExtenderContract
{
    /**
     * @var array<class-string>
     */
    private array $mediaModels = [
        Page::class,
        Site::class,
        Theme::class,
        Layout::class,
        Type::class,
        Translation::class,
    ];

    public function extend(): void
    {
        foreach ($this->mediaModels as $modelClass) {
            if (method_exists($modelClass, 'bootInteractsWithMedia')) {
                // Already handled by Eloquent's trait boot method
            }
        }
    }
}
