<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $title
 */
class LayoutBuilderNonPublishableAsset extends Model
{
    /** @use HasFactory<Factory<self>> */
    use HasFactory;

    protected $table = 'layout_builder_non_publishable_assets';

    protected $guarded = [];
}
