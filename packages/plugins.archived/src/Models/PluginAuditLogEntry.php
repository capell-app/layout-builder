<?php

declare(strict_types=1);

namespace Capell\Plugins\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginAuditLogEntry extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'marketplace_plugin_audit_log';

    protected $guarded = [];

    protected $casts = [
        'data' => AsArrayObject::class,
        'created_at' => 'datetime',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(MarketplacePlugin::class, 'marketplace_plugin_id');
    }
}
