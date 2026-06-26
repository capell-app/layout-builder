<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

final class LayoutContainerSchemaContextData extends Data
{
    public function __construct(
        public readonly ?Layout $layout,
        public readonly ?string $containerKey,
        public readonly ?string $themeKey,
        public readonly ?int $siteId,
        public readonly ?string $siteKey,
    ) {}

    public static function fromSchema(Schema $schema, ?string $containerKey = null): self
    {
        $record = $schema->getRecord();

        return self::fromLayout($record instanceof Layout ? $record : null, $containerKey);
    }

    public static function fromLayout(?Layout $layout, ?string $containerKey = null): self
    {
        if (! $layout instanceof Layout) {
            return new self(
                layout: null,
                containerKey: $containerKey,
                themeKey: null,
                siteId: null,
                siteKey: null,
            );
        }

        $layout->loadMissing(['theme', 'site.theme']);

        $site = $layout->getRelationValue('site');
        $site = $site instanceof Site ? $site : null;
        $theme = $layout->getRelationValue('theme');
        $theme = $theme instanceof Theme ? $theme : $site?->getRelationValue('theme');
        $theme = $theme instanceof Theme ? $theme : null;

        return new self(
            layout: $layout,
            containerKey: $containerKey,
            themeKey: self::themeKey($theme),
            siteId: self::siteId($layout, $site),
            siteKey: self::stringAttribute($site, 'key'),
        );
    }

    private static function themeKey(?Theme $theme): ?string
    {
        if (! $theme instanceof Theme) {
            return null;
        }

        return self::stringAttribute($theme, 'key');
    }

    private static function siteId(Layout $layout, ?Site $site): ?int
    {
        $siteId = $layout->getAttribute('site_id') ?? $site?->getKey();

        if (is_int($siteId)) {
            return $siteId;
        }

        return is_numeric($siteId) ? (int) $siteId : null;
    }

    private static function stringAttribute(?Model $model, string $key): ?string
    {
        if (! $model instanceof Model || ! array_key_exists($key, $model->getAttributes())) {
            return null;
        }

        $value = $model->getAttribute($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
