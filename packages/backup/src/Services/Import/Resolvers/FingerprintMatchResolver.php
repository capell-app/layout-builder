<?php

declare(strict_types=1);

namespace Capell\Backup\Services\Import\Resolvers;

use Illuminate\Database\Eloquent\Model;

/**
 * Match a shared relation by a structural fingerprint of its canonical
 * schema definition.
 *
 * Use for Layouts and Types, where the developer-chosen "key" might drift
 * across environments but the field schema (columns in the admin/meta
 * JSON) is identical. We build a fingerprint by recursively sorting the
 * relevant JSON structures by key, stripping volatile fields (timestamps,
 * ids), json-encoding, and sha256-ing the result.
 *
 * Confidence is intentionally 0.7 — below uuid/key (1.0) and slightly
 * higher than a normalised-name fallback. A fingerprint match says "this
 * record has an identical schema shape", which is stronger than "the
 * name looks similar" but weaker than "the stable key matched".
 *
 * @template TModel of Model
 */
final readonly class FingerprintMatchResolver implements MatchResolver
{
    private const CONFIDENCE = 0.7;

    private const VOLATILE_KEYS = [
        'id',
        'uuid',
        'created_at',
        'updated_at',
        'deleted_at',
        'site_id',
        'theme_id',
        'order',
    ];

    /**
     * @param  class-string<TModel>  $modelClass
     * @param  list<string>  $schemaColumns  attribute names that together form the canonical schema
     */
    public function __construct(
        private string $modelClass,
        private array $schemaColumns = ['admin', 'meta'],
    ) {}

    public function resolve(array $descriptor): ?MatchResolution
    {
        $attributes = $descriptor['attributes'] ?? null;
        if (! is_array($attributes)) {
            return null;
        }

        $incomingFingerprint = $this->fingerprint($attributes);
        if ($incomingFingerprint === null) {
            return null;
        }

        /** @var iterable<Model> $candidates */
        $candidates = $this->modelClass::query()->get();

        foreach ($candidates as $candidate) {
            $localAttributes = $candidate->attributesToArray();
            if ($this->fingerprint($localAttributes) === $incomingFingerprint) {
                return new MatchResolution(
                    localId: $candidate->getKey(),
                    strategy: 'fingerprint',
                    confidence: self::CONFIDENCE,
                    reason: 'schema fingerprint match',
                );
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function fingerprint(array $attributes): ?string
    {
        $structure = [];
        foreach ($this->schemaColumns as $column) {
            $structure[$column] = $this->normaliseValue($attributes[$column] ?? null);
        }

        $hasContent = false;
        foreach ($structure as $value) {
            if (! in_array($value, [null, [], ''], true)) {
                $hasContent = true;

                break;
            }
        }

        if (! $hasContent) {
            return null;
        }

        return hash('sha256', json_encode($structure, JSON_THROW_ON_ERROR));
    }

    private function normaliseValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $normalised = [];
        foreach ($value as $key => $child) {
            if (is_string($key) && in_array($key, self::VOLATILE_KEYS, true)) {
                continue;
            }

            $normalised[$key] = $this->normaliseValue($child);
        }

        if (! $isList) {
            ksort($normalised);
        }

        return $normalised;
    }
}
