<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Assets;

use Filament\Support\Assets\Css;
use Override;

final class FileVersionedCss extends Css
{
    #[Override]
    public function getVersion(): string
    {
        $path = $this->getPath();

        if ($path === null || $this->isRemote()) {
            return parent::getVersion();
        }

        $modifiedAt = filemtime($path);

        if ($modifiedAt === false) {
            return parent::getVersion();
        }

        return (string) $modifiedAt;
    }
}
