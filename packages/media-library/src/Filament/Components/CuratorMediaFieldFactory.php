<?php

declare(strict_types=1);

namespace Capell\MediaLibrary\Filament\Components;

use Awcodes\Curator\Components\FormBuilder\CuratorPicker;
use Capell\Core\Contracts\Media\MediaFieldFactory;

/**
 * MediaFieldFactory implementation that returns a CuratorPicker Filament
 * field. Bound in the container by MediaLibraryServiceProvider so
 * Capell schemas that type-hint MediaFieldFactory render with Curator's
 * picker instead of Spatie's uploader.
 */
final class CuratorMediaFieldFactory implements MediaFieldFactory
{
    public function make(string $name): CuratorPicker
    {
        return CuratorPicker::make($name);
    }
}
