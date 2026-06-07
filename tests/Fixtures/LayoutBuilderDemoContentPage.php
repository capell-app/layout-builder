<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Concerns\HasCapellMedia;
use Capell\Core\Contracts\Media\HasMediaContract;
use Capell\Core\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Override;
use Spatie\MediaLibrary\HasMedia;

final class LayoutBuilderDemoContentPage extends Model implements HasMedia, HasMediaContract
{
    use HasCapellMedia;

    public static int $defaultSiteId;

    public static int $defaultLayoutId;

    public static int $defaultBlueprintId;

    protected $table = 'pages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'blueprint_id',
        'layout_id',
        'meta',
        'name',
        'order',
        'parent_id',
        'site_id',
        'uuid',
    ];

    #[Override]
    public function getMorphClass(): string
    {
        return self::class;
    }

    /**
     * @return MorphMany<Translation, $this>
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'json',
        ];
    }
}
