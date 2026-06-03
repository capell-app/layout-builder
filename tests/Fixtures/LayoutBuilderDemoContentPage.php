<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Page;
use Illuminate\Support\Str;
use Override;

final class LayoutBuilderDemoContentPage extends Page
{
    public static int $defaultSiteId;

    public static int $defaultLayoutId;

    public static int $defaultBlueprintId;

    protected $table = 'pages';

    #[Override]
    public function getMorphClass(): string
    {
        return (new Page)->getMorphClass();
    }

    #[Override]
    protected static function booted(): void
    {
        self::creating(function (self $page): void {
            $page->uuid ??= Str::uuid()->toString();
            $page->site_id ??= self::$defaultSiteId;
            $page->layout_id ??= self::$defaultLayoutId;
            $page->blueprint_id ??= self::$defaultBlueprintId;
        });
    }
}
