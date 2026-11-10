<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Models;

use Capell\Core\Concerns\HasCapellTable;
use Capell\Core\Models\Layout;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class LayoutPresetUsage extends Model
{
    use HasCapellTable;

    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $table = 'layout_preset_usages';

    protected $fillable = [
        'preset_id',
        'preset_item_id',
        'layout_id',
        'container_key',
        'layout_updated_at',
    ];

    /** @return BelongsTo<LayoutPreset, $this> */
    public function preset(): BelongsTo
    {
        return $this->belongsTo(LayoutPreset::class, 'preset_id');
    }

    /** @return BelongsTo<Layout, $this> */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class, 'layout_id');
    }

    /** @return array<string, string> */
    #[Override]
    protected function casts(): array
    {
        return [
            'layout_updated_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}
