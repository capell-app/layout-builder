<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $hash
 * @property string $scope
 * @property string|null $label
 * @property array<string, mixed> $signature
 * @property array<string, mixed>|null $manifest
 * @property string|null $critical_css_path
 * @property string $status
 */
class FrontendRenderProfile extends Model
{
    protected $fillable = [
        'critical_css_path',
        'generated_at',
        'hash',
        'label',
        'manifest',
        'scope',
        'signature',
        'status',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'manifest' => 'array',
        'signature' => 'array',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(FrontendOptimizationRun::class, 'render_profile_id');
    }
}
