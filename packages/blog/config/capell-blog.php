<?php

declare(strict_types=1);

use Capell\Blog\Livewire\Page\ArchivePage;
use Capell\Blog\Livewire\Page\BlogPage;

return [
    'livewire_components' => [
        'capell-blog::livewire.page.blog' => BlogPage::class,
        'capell-blog::livewire.page.archive' => ArchivePage::class,
    ],
];
