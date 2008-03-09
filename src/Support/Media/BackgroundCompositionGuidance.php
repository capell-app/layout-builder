<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Media;

use BackedEnum;
use Capell\Admin\Support\Media\MediaCompositionGuidancePresenter;
use Capell\Core\Models\Blueprint;
use Capell\Core\Support\Media\MediaCompositionGuidanceRegistry;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Attaches registered media composition guidance to a Layout Builder
 * background field (upload or URL input).
 *
 * The guidance key is chosen by the blueprint's `admin` authoring metadata:
 *
 *     'composition_guidance' => [
 *         'background_image' => 'hero-wide',                 // one guide for every variant
 *         // or
 *         'background_image' => [
 *             'default' => 'hero-wide',
 *             'variant_state' => 'meta.layout',              // form state path holding the variant
 *             'variants' => ['split' => 'hero-split'],
 *         ],
 *     ]
 *
 * The blueprint is read from live form state, so switching blueprint or
 * variant re-selects the guide. Guidance is rendered as decorative content
 * only: it is never part of form state, saved meta or public output. When the
 * guidance APIs are unavailable or nothing is configured the field is
 * returned without guidance.
 */
final class BackgroundCompositionGuidance
{
    public const string ADMIN_KEY = 'composition_guidance';

    public const string DEFAULT_FIELD = 'background_image';

    /**
     * @template TField of Field
     *
     * @param  TField  $field
     * @param  string  $guidanceField  Blueprint `admin.composition_guidance` entry to read.
     * @param  string|null  $fallbackKey  Developer-supplied guide used when the blueprint configures none.
     * @return TField
     */
    public static function apply(Field $field, string $guidanceField = self::DEFAULT_FIELD, ?string $fallbackKey = null): Field
    {
        if (! self::isAvailable()) {
            return $field;
        }

        return $field->aboveContent(static function (Field $component) use ($guidanceField, $fallbackKey): ?Htmlable {
            $key = self::resolveKey($component, $guidanceField) ?? $fallbackKey;

            if ($key === null) {
                return null;
            }

            return resolve(MediaCompositionGuidancePresenter::class)->render($key);
        });
    }

    public static function isAvailable(): bool
    {
        return class_exists(MediaCompositionGuidanceRegistry::class)
            && class_exists(MediaCompositionGuidancePresenter::class);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        if (! self::isAvailable()) {
            return [];
        }

        $options = [];

        foreach (resolve(MediaCompositionGuidanceRegistry::class)->all() as $key => $guidance) {
            $options[$key] = __($guidance->label);
        }

        return $options;
    }

    public static function resolveKey(Component $component, string $guidanceField = self::DEFAULT_FIELD): ?string
    {
        $state = self::rootState($component);

        return self::keyFor(self::blueprint($component, $state), $guidanceField, $state);
    }

    /**
     * @param  array<array-key, mixed>  $state
     */
    public static function keyFor(?Blueprint $blueprint, string $guidanceField = self::DEFAULT_FIELD, array $state = []): ?string
    {
        $config = data_get($blueprint?->admin, self::ADMIN_KEY . '.' . $guidanceField);

        if (is_string($config)) {
            return filled($config) ? $config : null;
        }

        if (! is_array($config)) {
            return null;
        }

        $variantState = $config['variant_state'] ?? null;
        $variants = $config['variants'] ?? null;

        if (is_string($variantState) && is_array($variants)) {
            $variant = data_get($state, $variantState);
            $variant = $variant instanceof BackedEnum ? $variant->value : $variant;

            if (is_scalar($variant) && is_string($variants[(string) $variant] ?? null) && filled($variants[(string) $variant])) {
                return $variants[(string) $variant];
            }
        }

        $default = $config['default'] ?? null;

        return is_string($default) && filled($default) ? $default : null;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function rootState(Component $component): array
    {
        try {
            $state = $component->getRootContainer()->getRawState();
        } catch (Throwable) {
            return [];
        }

        if ($state instanceof Arrayable) {
            $state = $state->toArray();
        }

        return is_array($state) ? $state : [];
    }

    /**
     * @param  array<array-key, mixed>  $state
     */
    private static function blueprint(Component $component, array $state): ?Blueprint
    {
        $record = $component->getRecord();
        $record = $record instanceof Model ? $record : null;
        // The record may be any model the schema is attached to, including a
        // Blueprint itself, which has no blueprint_id. Strict attribute access
        // throws rather than returning null, so only read a loaded attribute.
        $blueprintId = $state['blueprint_id'] ?? null;

        if ($blueprintId === null && $record !== null && array_key_exists('blueprint_id', $record->getAttributes())) {
            $blueprintId = $record->getAttribute('blueprint_id');
        }

        if ($record !== null && $record->relationLoaded('blueprint')) {
            $loaded = $record->getRelation('blueprint');

            if ($loaded instanceof Blueprint && ($blueprintId === null || (is_scalar($blueprintId) && is_scalar($loaded->getKey()) && (string) $loaded->getKey() === (string) $blueprintId))) {
                return $loaded;
            }
        }

        if (! is_numeric($blueprintId)) {
            return null;
        }

        return Blueprint::query()->find((int) $blueprintId);
    }
}
