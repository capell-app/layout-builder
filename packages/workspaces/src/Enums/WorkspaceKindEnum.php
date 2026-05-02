<?php

declare(strict_types=1);

namespace Capell\Workspaces\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum WorkspaceKindEnum: string implements HasColor, HasIcon, HasLabel
{
    /** Normal editorial workspace created by a user. */
    case Manual = 'manual';

    /** Created by the Recovery Center to stage an import package. */
    case Import = 'import';

    /** Created to stage a backup restore prior to publish. */
    case Restore = 'restore';

    /** Created by the WordPress / spreadsheet importer. */
    case WordPress = 'wordpress';

    /** Auto-created single-page draft (the "simple" save-as-draft flow). */
    case SinglePageDraft = 'single_page_draft';

    public function getColor(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Import => 'info',
            self::Restore => 'warning',
            self::WordPress => 'info',
            self::SinglePageDraft => 'info',
        };
    }

    public function getIcon(): string|Heroicon
    {
        return match ($this) {
            self::Manual => Heroicon::OutlinedPencilSquare,
            self::Import => Heroicon::OutlinedArrowDownTray,
            self::Restore => Heroicon::OutlinedArrowUturnLeft,
            self::WordPress => Heroicon::OutlinedGlobeAlt,
            self::SinglePageDraft => Heroicon::OutlinedDocumentText,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => __('capell-admin::workspace.kind.manual'),
            self::Import => __('capell-admin::workspace.kind.import'),
            self::Restore => __('capell-admin::workspace.kind.restore'),
            self::WordPress => __('capell-admin::workspace.kind.wordpress'),
            self::SinglePageDraft => __('capell-admin::workspace.kind.single_page_draft'),
        };
    }

    /** True when this workspace was produced by an automated Recovery Center flow. */
    public function isAutomated(): bool
    {
        return ! in_array($this, [self::Manual, self::SinglePageDraft], true);
    }
}
