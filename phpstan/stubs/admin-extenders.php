<?php

declare(strict_types=1);

namespace Capell\Admin\Contracts\Extenders;

interface PageHeaderActionExtender
{
    public const TAG = 'capell-admin:page-header-actions';

    /** @return array<int, object> */
    public function actions(): array;
}

interface SiteHeaderActionExtender
{
    public const TAG = 'capell-admin:site-header-actions';

    /** @return array<int, object> */
    public function actions(): array;
}
