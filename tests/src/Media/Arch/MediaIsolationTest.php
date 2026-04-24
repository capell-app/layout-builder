<?php

declare(strict_types=1);

use Capell\Workspaces\Providers\WorkspacesServiceProvider;

// Mosaic models (Section, Widget, WidgetAsset) and Blog\Article still use InteractsWithMedia
// and Media\Models\Media — tracked for removal as the media extraction completes.
// Core\Exchanger (capell-4 repo) uses Media\Models\Media — out of scope here.
// WorkspacesServiceProvider registers Media in the morph map.
arch()
    ->expect('Capell\Media')
    ->toOnlyBeUsedIn('Capell\Media')
    ->ignoring([
        'Capell\Admin',
        'Capell\Blog',
        'Capell\Core',
        'Capell\Mosaic',
        WorkspacesServiceProvider::class,
    ]);

arch()
    ->expect('Capell\Media')
    ->classes()
    ->toUseStrictEquality();
