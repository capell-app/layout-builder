<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\Core\Models\Layout;

/**
 * The layout an authorised editor is changing, including unsaved containers.
 * Companion widget schemas read it instead of depending on the editor component.
 */
interface LayoutEditorState
{
    public Layout $layout { get; }

    /**
     * @var array<string, array<string, mixed>>|null
     */
    public ?array $containers { get; }
}
