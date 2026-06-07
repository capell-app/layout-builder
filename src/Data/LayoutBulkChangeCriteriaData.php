<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

final readonly class LayoutBulkChangeCriteriaData
{
    /**
     * @param  list<int>  $siteIds
     * @param  list<int>  $themeIds
     * @param  list<string>  $groups
     * @param  list<string>  $layoutKeys
     */
    public function __construct(
        public array $siteIds = [],
        public array $themeIds = [],
        public array $groups = [],
        public array $layoutKeys = [],
        public bool $activeOnly = true,
        public ?string $requireWidgetKey = null,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromPayload(array $payload): self
    {
        return new self(
            siteIds: self::integerList($payload['site_ids'] ?? []),
            themeIds: self::integerList($payload['theme_ids'] ?? []),
            groups: self::stringList($payload['groups'] ?? []),
            layoutKeys: self::stringList($payload['layout_keys'] ?? []),
            activeOnly: (bool) ($payload['active_only'] ?? true),
            requireWidgetKey: self::nullableString($payload['require_widget_key'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return [
            'site_ids' => $this->siteIds,
            'theme_ids' => $this->themeIds,
            'groups' => $this->groups,
            'layout_keys' => $this->layoutKeys,
            'active_only' => $this->activeOnly,
            'require_widget_key' => $this->requireWidgetKey,
        ];
    }

    /** @return list<int> */
    private static function integerList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn (mixed $item): int => is_numeric($item) ? (int) $item : 0, $value),
            fn (int $item): bool => $item > 0,
        )));
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn (mixed $item): string => trim((string) $item), $value),
            fn (string $item): bool => $item !== '',
        )));
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_string($value) || is_numeric($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
