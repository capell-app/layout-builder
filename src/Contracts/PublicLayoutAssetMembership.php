<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Contracts;

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Illuminate\Database\Eloquent\Model;

interface PublicLayoutAssetMembership
{
    /** Check current public attachment eligibility without rendering widget payloads. */
    public function contains(Model $asset, Page $page, Layout $layout, Language $language): bool;
}
