<?php

declare(strict_types=1);

namespace Capell\Admin\Filament\Concerns;

trait HasNavigationBadge
{
    public static function getNavigationBadge(): ?string {}
}

namespace Capell\Admin\Filament\Components\Forms;

use Filament\Forms\Components\Field;

class NavigationSelect extends Field
{
    public static function make(string $name): static {}
}

namespace Capell\Admin\Data\Dashboard;

use Spatie\LaravelData\Data;

class MergeHistoryEntryData extends Data
{
    public function __construct(
        public readonly int $workspaceId,
        public readonly string $name,
        public readonly string $actorName,
        public readonly int $pageCount,
        public readonly int $durationOpenHours,
        public readonly string $publishedAt,
    ) {}
}

namespace Capell\Core\Enums;

enum NavigationItemType: string
{
    case Page = 'page';
    case Link = 'link';
}

namespace Capell\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Navigation extends Model
{
    public static function factory(): void {}
}
