<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $site_id
 * @property string $pageable_type
 * @property int $pageable_id
 * @property int $language_id
 * @property int|null $layout_id
 * @property int|null $theme_id
 * @property string $render_profile
 * @property string $owner_revision
 * @property string $context_fingerprint
 * @property string $target_instance_id
 * @property string $widget_key
 * @property int $definition_state_version
 * @property array<string, mixed> $encrypted_payload
 * @property CarbonImmutable|null $superseded_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $revoked_at
 */
final class PublicWidgetSnapshot extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'site_id', 'pageable_type', 'pageable_id', 'language_id', 'layout_id', 'theme_id',
        'render_profile', 'owner_revision', 'context_fingerprint', 'target_instance_id',
        'widget_key', 'definition_state_version', 'encrypted_payload', 'superseded_at',
        'expires_at', 'revoked_at',
    ];

    public function isAvailable(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'site_id' => 'integer',
            'pageable_id' => 'integer',
            'language_id' => 'integer',
            'layout_id' => 'integer',
            'theme_id' => 'integer',
            'definition_state_version' => 'integer',
            'encrypted_payload' => 'encrypted:array',
            'superseded_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
